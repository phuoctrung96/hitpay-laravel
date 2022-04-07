<?php

namespace App\Jobs;

use App\Business;
use App\Notifications\SendFile;
use App\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Date;
use League\Csv\Writer;

class SendExportedCommissions implements ShouldQueue
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

        $transfers = $this->business->commissions()->with('charges');

        if (key_exists('from_date', $this->data) && key_exists('to_date', $this->data)) {
            $fromDate = Date::parse($this->data['from_date']);
            $toDate = Date::parse($this->data['to_date']);
        } else {
            $today = Date::now();
            $fromDate = $today->startOfMonth();
            $toDate = $today->endOfMonth();
        }

        if ($fromDate->gt($toDate)) {
            [$fromDate, $toDate] = [$toDate, $fromDate];
        }

        $transfers->whereDate('created_at', '>=', $fromDate->startOfDay()->toDateTimeString());
        $transfers->whereDate('created_at', '<=', $toDate->endOfDay()->toDateTimeString());

        $transfers = $transfers->orderBy('created_at')->get();

        $csv = Writer::createFromString('');

        $csv->insertOne([
            '#',
            'Payout ID',
            'Currency',
            'Total Sales',
            'Net Payout Amount',
            'Charges Count',
            'Status',
            'Payout Date',
        ]);

        $i = 1;

        $data = [];

        foreach ($transfers as $transfer) {
            $orderId = null;
            $orderedProducts = null;

            $singleData = [
                '#' => $i++,
                'Payout ID' => $transfer->getKey(),
                'Currency' => $transfer->currency,
                'Total Sales' => getReadableAmountByCurrency($transfer->currency, $transfer->charges->sum('amount')),
                'Net Payout Amount' => getReadableAmountByCurrency($transfer->currency, $transfer->amount),
                'Charges Count' => $transfer->charges->count(),
                'Status' => ucwords(str_replace('_', ' ', $transfer->status)),
                'Payout Date' => $transfer->created_at->toDateString(),
            ];

            $data[] = $singleData;
        }

        $csv->insertAll($data);

        $fromDate = $fromDate->toDateString();
        $toDate = $toDate->toDateString();

        if ($this->user instanceof User) {
            $this->user->notify(new SendFile($this->business->getName().' - Exported HitPay Balance Payouts', [
                'Please find attached the exported HitPay Balance payouts from '.$fromDate.' to '.$toDate,
            ], ($fromDate.' - '.$toDate), $csv->getContent()));
        } else {
            $this->business->notify(new SendFile('Your Exported HitPay Balance Payouts', [
                'Please find attached your exported HitPay Balance Payouts from '.$fromDate.' to '.$toDate,
            ], ($fromDate.' - '.$toDate), $csv->getContent()));
        }
    }
}
