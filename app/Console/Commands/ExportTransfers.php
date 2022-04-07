<?php

namespace App\Console\Commands;

use App\Business\Charge;
use App\Business\Transfer;
use App\Console\Commands\Helpers\HasDateTimeRange;
use App\Console\Commands\Helpers\StoresLocalFile;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Str;
use League\Csv\Writer;
use SplTempFileObject;

class ExportTransfers extends Command
{
    use HasDateTimeRange, StoresLocalFile;

    /**
     * @inheritdoc
     */
    protected $signature = 'export:transfers
                            { --email= : The receiver\'s email, leave blank and will send to all admins. }
                            { --date= : Get the summary for selected date. }
                            { --start_date= : Set the start date of the summary, will be ignored if a date is given. }
                            { --range= : Set the range from the start date. }
                            { --end_date= : Set the end date of the summary, will be ignored if a range is given. }
                            { --period=yesterday : Get the summary for selected period, will be ignored if a date or start date is given. }';

    /**
     * @inerhitdoc
     */
    protected $description = 'Export the transfer records, send to the given email or all admins.';

    /**
     * Execute the console command.
     *
     * @throws \League\Csv\CannotInsertRecord
     * @throws \Exception
     */
    public function handle()
    {
        $email = $this->option('email') ?? 'aditya@hit-pay.com';

        [ $startDate, $endDate ] = $this->getDateTimeRange($this->options());

        $query = Transfer::query();

        $query->with([
            'charges',
            'business' => function (Relation $query) {
                /**
                 * @var \App\Business $query
                 */
                $query->withTrashed();
            },
        ]);

        $query->where('payment_provider', 'dbs_sg');

        $succeededTransfers = clone $query;

        $succeededTransfers->whereIn('status', [ 'succeeded', 'succeeded_manually' ]);
        $succeededTransfers->whereDate('transferred_at', '>=', $startDate->toDateTimeString());
        $succeededTransfers->whereDate('transferred_at', '<=', $endDate->toDateTimeString());

        $succeededTransfersCsv = $this->insertIntoCsv($succeededTransfers);

        $this->storeAndGroupByDate(
            $startDate,
            $endDate,
            $email,
            'transfers-succeeded',
            $succeededTransfersCsv->getContent()
        );

        $pendingTransfers = clone $query;

        $pendingTransfers->where('status', 'request_pending');

        $pendingTransfersCsv = $this->insertIntoCsv($pendingTransfers);

        $this->storeAndGroupByDate(
            $startDate,
            $endDate,
            $email,
            'transfers-pending',
            $pendingTransfersCsv->getContent()
        );
    }

    private function insertIntoCsv(Builder $query) : Writer
    {
        $csv = Writer::createFromFileObject(new SplTempFileObject);

        $csv->insertOne([
            '#',
            'ID',
            'DBS ID',
            'Business ID',
            'Business Name',
            'Platform',
            'Transfer Type',
            'Currency',
            'Amount',
            'Remark',
            'Status',
            'Failed Reason',
            'Retried',
            'Receiver Name',
            'Receiver Bank Name',
            'Receiver Bank Account',
            'Charge IDs',
            'Created Date',
        ]);

        $counter = 0;

        $query->each(function (Transfer $transfer) use (&$counter, &$csv) {
            $referenceId = str_replace('-', '', $transfer->id);
            $status = $transfer->status;

            if (is_int($transfer->counter)) {
                $referenceId = "{$referenceId}-{$transfer->counter}";

                if ($status === 'succeeded') {
                    $status = "{$status}_retried";

                    // We add 1 here because if succeeded after retried, it will not update the counter. E.g. payment
                    // failed at first time, it will set counter to 0. If after retried it succeeded, it will still 0,
                    // but status succeeded.
                    //
                    $_counter = $transfer->counter + 1;
                }
            }

            $failedReason = null;

            if (!Str::startsWith($status, 'succeeded')) {
                if (isset($transfer->data['requests'])) {
                    $requestsData = collect($transfer->data['requests']);

                    foreach ($requestsData->pluck('response.txnResponse')->where('txnStatus', 'RJCT') as $response) {
                        $timestamp = $response['txnSettlementDt'] ?? 'Unknown Time';
                        $rejectCode = $response['txnRejectCode'] ?? 'Unknown Code';
                        $reason = $response['txnStatusDescription'] ?? 'Unknown Reason';

                        $failedReasons[] = "{$timestamp} - [{$rejectCode}] {$reason}";
                    }

                    if (isset($failedReasons)) {
                        $failedReason = implode("\n", $failedReasons);
                    } else {
                        $failedReason = 'No failed response logged. Check with backend.';
                    }
                } else {
                    $failedReason = 'No request found for pending transfer. Check with backend.';
                }
            }

            $readableAmount = getReadableAmountByCurrency($transfer->currency, $transfer->amount);

            if (isset($transfer->data['account']['swift_code'])) {
                $swiftCode = $transfer->data['account']['swift_code'];

                $bankName = Transfer::$availableBankSwiftCodes[$swiftCode] ?? $swiftCode;
            }

            $chargeIds = $transfer->charges->map(function (Charge $charge) {
                $formattedAmount = getFormattedAmount($charge->currency, $charge->amount);

                $netAmount = $charge->home_currency_amount - $charge->getTotalFee();

                $formattedNetAmount = getFormattedAmount($charge->home_currency, $netAmount);

                return "Charge ID: {$charge->id} - {$formattedAmount} (Net: {$formattedNetAmount})";
            })->implode("\r\n");

            if ($transfer->transferred_at instanceof Carbon) {
                $transferredAt = $transfer->transferred_at->toDateTimeLocalString();
            }

            $csv->insertOne([
                ++$counter,
                $transfer->id,
                $referenceId,
                $transfer->business_id,
                $transfer->business->name,
                $transfer->payment_provider,
                $transfer->payment_provider_transfer_type,
                $transfer->currency,
                $readableAmount,
                $transfer->remark,
                $status,
                $failedReason,
                $_counter ?? null,
                $transfer->data['account']['name'] ?? null,
                $bankName ?? null,
                $transfer->data['account']['number'] ?? null,
                $chargeIds,
                $transferredAt ?? null,
            ]);
        });

        return $csv;
    }
}
