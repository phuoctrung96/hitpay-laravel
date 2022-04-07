<?php

namespace App\Jobs;

use App\Business;
use App\Enumerations\PaymentProvider;
use App\Notifications\SendFile;
use App\Role;
use App\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Date;
use League\Csv\Writer;

class SendExportedTransfers implements ShouldQueue
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

        $transfers = $this->business->transfers()->whereIn('payment_provider', [
            PaymentProvider::DBS_SINGAPORE,
        ]);

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
            'Net Payout Amount',
            'Status',
            'Payout Date',
        ]);

        $i = 1;

        $data = [];

        foreach ($transfers as $transfer) {
            $data[] = [
                '#' => $i++,
                'Payout ID' => $transfer->getKey(),
                'Currency' => $transfer->currency,
                'Net Payout Amount' => getReadableAmountByCurrency($transfer->currency, $transfer->amount),
                'Status' => ucwords(str_replace('_', ' ', $transfer->status)),
                'Payout Date' => $transfer->created_at->toDateString(),
            ];
        }

        $csv->insertAll($data);

        $fromDate = $fromDate->toDateString();
        $toDate = $toDate->toDateString();

        if ($this->user instanceof User) {
            $this->user->notify(new SendFile($this->business->getName().' - Exported PayNow Payouts', [
                'Please find attached the exported PayNow payouts from '.$fromDate.' to '.$toDate,
            ], ($fromDate.' - '.$toDate), $csv->getContent()));
        } else {
            $this->business->notify(new SendFile('Your Exported HitPay Balance Payouts', [
                'Please find attached your exported HitPay Balance payouts from '.$fromDate.' to '.$toDate,
            ], ($fromDate.' - '.$toDate), $csv->getContent()));
        }

        /** @var Business\BusinessUser $businessAdmins */
        $businessAdmins = $this->business->businessUsers()
            ->with('user')
            ->where('role_id', Role::admin()->id)
            ->get();

        foreach ($businessAdmins as $businessAdmin) {
            $businessAdmin->user->notify(new SendFile('Your Exported HitPay Balance Payouts', [
                'Please find attached your exported HitPay Balance payouts from '.$fromDate.' to '.$toDate,
            ], ($fromDate.' - '.$toDate), $csv->getContent()));
        }
    }
}
