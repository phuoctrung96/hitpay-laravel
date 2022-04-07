<?php

namespace App\Jobs;

use App\Business;
use App\Enumerations\Business\Wallet\Event;
use App\Notifications\SendFile;
use App\Role;
use App\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Log;
use League\Csv\Writer;

class SendExportedHitPayPayoutBreakdown implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $business;

    public $data;

    public $user;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Business $business, array $data, User $user = null)
    {
        $this->business = $business;
        $this->data = $data;
        $this->user = $user;
    }

    /**
     * @throws \League\Csv\CannotInsertRecord
     */
    public function handle()
    {
        ini_set('memory_limit', '512M');

        if (key_exists('from_date', $this->data) && key_exists('to_date', $this->data)) {
            $fromDate = Date::parse($this->data['from_date']);
            $toDate = Date::parse($this->data['to_date']);
        } else {
            $today = Date::now();
            $fromDate = $today->startOfMonth();
            $toDate = $today->endOfMonth();
        }

        if ($fromDate->gt($toDate)) {
            [ $fromDate, $toDate ] = [ $toDate, $fromDate ];
        }

        $wallet = $this->business->availableBalance($this->business->currency);

        $transactions = $wallet->transactions()->with('relatable');

        $transactions->whereBetween('created_at', [ $fromDate->toDateTimeString(), $toDate->toDateTimeString() ]);

        $csv = Writer::createFromString('');

        $csv->insertOne([
            '#',
            'Datetime',
            'Description',
            'Charge ID',
            'Order ID',
            'Debit',
            'Credit',
            'Balance',
        ]);

        $i = 1;

        $data = [];

        $currency = $this->business->currency;

        // TODO - Bankorh
        //   -------------->>>
        //   -
        //   The eager loading part needs enhancement to reduce database calls.
        //
        $transactions->each(function (Business\Wallet\Transaction $transaction) use (&$i, &$data, $currency) {
            if ($transaction->relatable instanceof Business\Wallet\Transaction
                && $transaction->relatable->event === Event::RECEIVED_FROM_CHARGE) {

                if ($transaction->relatable->relatable instanceof Business\Charge) {
                    $charge = $transaction->relatable->relatable;
                    $chargeId = $charge->getKey();

                    if ($charge->business_target_type === 'business_order') {
                        $orderId = $charge->business_target_id;
                    } else {
                        $orderId = $charge->plugin_provider_order_id ?? $charge->plugin_provider_reference ?? null;
                    }
                } else {
                    Log::alert("Transaction #{$transaction->id} is having event `{$transaction->event}` but the relatable charge isn't found.");
                }
            }

            $data[] = [
                '#' => $i++,
                'Datetime' => $transaction->created_at->toDateTimeString(),
                'Description' => $transaction->description,
                'Charge ID' => $chargeId ?? null,
                'Order ID' => $orderId ?? null,
                'Debit' => $transaction->amount < 0 ? getReadableAmountByCurrency($currency, $transaction->amount) : '-',
                'Credit' => $transaction->amount > 0 ? getReadableAmountByCurrency($currency, $transaction->amount) : '-',
                'Balance' => getReadableAmountByCurrency($currency, $transaction->balance_after),
            ];
        });

        $csv->insertAll($data);

        $fromDate = $fromDate->toDateTimeString();
        $toDate = $toDate->toDateTimeString();

        if ($this->user instanceof User) {
            $this->user->notify(new SendFile($this->business->getName().' - Exported PayNow Payouts', [
                'Please find attached the exported PayNow payouts from '.$fromDate.' to '.$toDate,
            ], ( $fromDate.' - '.$toDate ), $csv->getContent()));
        } else {
            $this->business->notify(new SendFile('Your Exported HitPay Balance Payouts', [
                'Please find attached your exported HitPay Balance payouts from '.$fromDate.' to '.$toDate,
            ], ( $fromDate.' - '.$toDate ), $csv->getContent()));
        }

        /** @var Business\BusinessUser $businessAdmins */
        $businessAdmins = $this->business->businessUsers()
            ->with('user')
            ->where('role_id', Role::admin()->id)
            ->get();

        foreach ($businessAdmins as $businessAdmin) {
            $businessAdmin->user->notify(new SendFile('Your Exported HitPay Balance Payouts', [
                'Please find attached your exported HitPay Balance payouts from '.$fromDate.' to '.$toDate,
            ], ( $fromDate.' - '.$toDate ), $csv->getContent()));
        }
    }
}
