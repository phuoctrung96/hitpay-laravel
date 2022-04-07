<?php

namespace App\Console\Commands\Providers\GrabPay;

use App\Business;
use App\Business\PaymentProvider;
use Illuminate\Console\Command;
use League\Csv\Writer;
use App\Enumerations\PaymentProvider as PaymentProviderEnum;
use App\Mail\GrabPayMassOnboarding;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Arr;

class MassOnboarding extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'grabpay:mass-onboarding {email}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate & send GrabPay mass onboarding file';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
      $businesses = Business::where('verified_wit_my_info_sg', true)
        ->whereHas('verifications', function ($query) {
          $query->where('type', 'business');
        })->whereDoesntHave('paymentProviders', function ($query) {
          $query->where('payment_provider', PaymentProviderEnum::GRABPAY);
          $query->where('onboarding_status', 'success');    
        }
      )->with('verifications')
      ->with('merchantCategory')
      ->get();

      $csv = Writer::createFromString('');

      $csv->insertOne([
        's_no',
        'merchant_ref',
        'merchant_name',
        'trading_name',
        'business_type',
        'website',
        'business_registration',
        'country_of_registration',
        'address',
        'postal_code',
        'SSIC',            
        'merchant_category_code',            
        'submitted_date',
        'updated_date',
        'status',
        'merchant_id'
      ]);

      $data = [];
      $i = 1;

      foreach ($businesses as $rec) {
        $verification = $rec['verifications'][0];
        $entity_address = $verification['my_info_data']['data']['entity']['addresses']['addresses-list'][0] ?? [];
        $addrs = collect(Arr::only($entity_address, ['unit', 'block', 'floor', 'postal', 'street', 'country'
        ]))->map(function ($value, $key) {
            return strtoupper($key) . ' : ' . ($value['value'] ?? $value['desc']);
        })->implode(", ");

        $cat = $rec->merchantCategory;

        $data[] = [
          's_no' => $i++,
          'merchant_ref' => $rec['payment_provider_account_id'],
          'merchant_name' => $rec['name'],
          'trading_name' => '',
          'business_type' => 'Online',
          'website' => '',
          'business_registration' => $verification['my_info_data']['uen'] ?? '',
          'country_of_registration' => 'SG',
          'address' => $addrs,
          'postal_code' => $entity_address['postal'] ? $entity_address['postal']['value'] : '',
          'SSIC' => '',            
          'merchant_category_code' => $cat ? $cat->code : '',
          'submitted_date' => date('d-m-Y H:i:s'),
          'updated_date' => '',
          'status' => $rec['onboarding_status'],
          'merchant_id' => ''
        ];
      }

      $csv->insertAll($data);      

      Mail::to($this->argument('email'))->send(new GrabPayMassOnboarding($csv));
    }
}