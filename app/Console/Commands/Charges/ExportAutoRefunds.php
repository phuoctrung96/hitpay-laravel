<?php

namespace App\Console\Commands\Charges;

use App\Console\Commands\Helpers\HasDateTimeRange;
use App\Console\Commands\Helpers\StoresLocalFile;
use App\Models\Business\Charge\AutoRefund;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Relations\Relation;
use League\Csv\Writer;
use SplTempFileObject;

class ExportAutoRefunds extends Command
{
    use HasDateTimeRange, StoresLocalFile;

    /**
     * @inerhitdoc
     */
    protected $signature = 'export:charges.auto-refunds
                            { --email= : The receiver\'s email, leave blank and will send to all admins. }
                            { --date= : Get the summary for selected date. }
                            { --start_date= : Set the start date of the summary, will be ignored if a date is given. }
                            { --range= : Set the range from the start date. }
                            { --end_date= : Set the end date of the summary, will be ignored if a range is given. }
                            { --period=yesterday : Get the summary for selected period, will be ignored if a date or start date is given. }';

    /**
     * @inerhitdoc
     */
    protected $description = 'Export the auto refund records for the charges, send to the given email or all admins.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        [ $startDate, $endDate ] = $this->getDateTimeRange($this->options());

        $csv = Writer::createFromFileObject(new SplTempFileObject);

        $csv->insertOne([
            '#',
            'Auto Refund ID',
            'DBS ID',
            'Business ID',
            'Business Name',
            'Charge ID',
            'Payment Intent ID',
            'Customer Name',
            'Customer Email',
            'Currency',
            'Amount',
            'Refund ID',
            'Transaction Reference ID',
            'Auto Refunded Date',
            'Payment Intent Created Date',
            'Payment Intent Last Updated Date',
        ]);

        $autoRefunds = AutoRefund::query();

        $autoRefunds->with([
            'charge',
            'business' => function (Relation $query) {
                $query->withTrashed();
            },
            'paymentIntent',
        ]);

        $autoRefunds->whereDate('refunded_at', '>=', $startDate->toDateTimeString());
        $autoRefunds->whereDate('refunded_at', '<=', $endDate->toDateTimeString());

        $counter = 0;

        $autoRefunds->each(function (AutoRefund $autoRefund) use (&$counter, &$csv) {
            $csv->insertOne([
                ++$counter,
                $autoRefund->id,
                str_replace('-', '', $autoRefund->id),
                $autoRefund->business_id,
                $autoRefund->business->name,
                $autoRefund->charge->id,
                $autoRefund->paymentIntent->id,
                $autoRefund->charge->customer_name,
                $autoRefund->charge->customer_email,
                $autoRefund->currency,
                getReadableAmountByCurrency($autoRefund->currency, $autoRefund->amount),
                $autoRefund->payment_provider_refund_id,
                $autoRefund->additional_reference,
                $autoRefund->refunded_at,
                $autoRefund->paymentIntent->created_at,
                $autoRefund->paymentIntent->updated_at,
            ]);
        });

        $email = $this->option('email') ?? 'aditya@hit-pay.com';

        $this->storeAndGroupByDate($startDate, $endDate, $email, 'auto-refunds', $csv->getContent());
    }
}
