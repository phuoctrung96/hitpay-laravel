<?php

namespace App\Jobs;

use App\Business;
use App\Business\Order;
use App\Business\SubscriptionPlan;
use App\Notifications\SendFile;
use App\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use League\Csv\Writer;

class SendExportedCustomers implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $business;

    public $user;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Business $business, User $user = null)
    {
        $this->business = $business;
        $this->user = $user;
    }

    /**
     * @throws \League\Csv\CannotInsertRecord
     */
    public function handle()
    {
        ini_set('memory_limit', '512M');

        $customers = $this->business->customers()->get();

        $csv = Writer::createFromString('');

        $csv->insertOne([
            '#',
            'Name',
            'Email',
            'Phone Number',
            'Address',
        ]);

        $i = 1;

        $data = [];

        /** @var \App\Business\Customer $customer */
        foreach ($customers as $customer) {
            $singleData = [
                '#' => $i++,
                'Name' => $customer->name,
                'Email' => $customer->email,
                'Phone Number' => $customer->phone_number,
                'Address' => implode(', ', array_filter([
                    $customer->street,
                    $customer->city,
                    $customer->state,
                    $customer->postal_code,
                    $customer->country,
                ])),
            ];

            $data[] = $singleData;
        }

        $csv->insertAll($data);

        if ($this->user instanceof User) {
            $this->user->notify(new SendFile($this->business->getName().' - Exported Customers', [
                'Please find attached the exported customers',
            ], 'customers', $csv->getContent()));
        } else {
            $this->business->notify(new SendFile('Your Exported Customers', [
                'Please find attached your exported customers',
            ], 'customers', $csv->getContent()));
        }
    }
}
