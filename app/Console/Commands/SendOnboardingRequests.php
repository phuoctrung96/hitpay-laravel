<?php

namespace App\Console\Commands;

use App\Business;
use App\Business\PaymentProvider;
use App\Enumerations\PaymentProvider as PaymentProviderEnum;
use App\Enumerations\OnboardingStatus;
use App\Mail\OnboardingRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use League\Csv\Writer;

class SendOnboardingRequests extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hitpay:send-onboarding';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send onboarding requests';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle() : int
    {
        $providers_info = [
          [
            'slug' => PaymentProviderEnum::GRABPAY,
            'email' => 'services.grabpay.onboarding_email'
          ]
        ];

        foreach ($providers_info as $provider_info) {
          $providers = PaymentProvider::where('payment_provider', $provider_info['slug'])
            ->where('onboarding_status', OnboardingStatus::PENDING_SUBMISSION)
            ->get();

          $i = 1;
          $data = [];
          $csv = Writer::createFromString('');

          switch ($provider_info['slug']) {
            case PaymentProviderEnum::GRABPAY:
              $csv->insertOne([
                's_no',
                'merchant_ref',
                'merchant_name',
                'business_type',
                'business_registration',
                'country_of_registration',
                'address',
                'merchant_category_code',
                'submitted_date',
                'updated_date',
                'status',
              ]);

              foreach ($providers as $provider) {
                // remove , from address string
                $address = str_replace(',', '', $provider->data['city'] . " " . $provider->data['address'] . " " . $provider->data['postal_code']);

                $data[] = [
                  's_no' => $i++,
                  'merchant_ref' => $provider->payment_provider_account_id,
                  'merchant_name' => $provider->business->name,
                  'business_type' => 'Online',
                  'business_registration' => $provider->data['company_uen'],
                  'country_of_registration' => 'SG',
                  'address' => $address,
                  'merchant_category_code' => '1001',
                  'submitted_date' => $provider->created_at,
                  'updated_date' => '',
                  'status' => OnboardingStatus::PENDING_SUBMISSION
                ];
              }

              break;
          }

          $csv->insertAll($data);

          Mail::to(Config::get($provider_info['email']))->send(new OnboardingRequest($csv));

          // Update status to pending_verification
          DB::transaction(function () use ($providers) {
            foreach ($providers as $provider) {
              $provider->onboarding_status = OnboardingStatus::PENDING_VERIFICATION;
              $provider->save();
            }
          });
        }

        return 0;
    }
}
