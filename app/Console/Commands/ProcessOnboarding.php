<?php

namespace App\Console\Commands;

use App\Enumerations\OnboardingStatus;
use App\Enumerations\PaymentProvider as PaymentProviderEnum;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use League\Csv\Reader;
use League\Csv\Writer;
use App\Business\PaymentProvider;

class ProcessOnboarding extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hitpay:process-onboarding';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process Onboarding CSVs';

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
          'path' => 'onboarding/grabpay'
        ],
        [
          'slug' => PaymentProviderEnum::HOOLAH,
          'path' => 'onboarding/hoolah'
        ]
      ];

      foreach ($providers_info as $provider_info) {
        // Get list of files and filter by extension
        $files = array_filter(Storage::disk('s3')->files($provider_info['path']), function ($value) {
          return str_ends_with($value, '.csv');
        });

        foreach ($files as $file) {
          $csvData = Storage::disk('s3')->get($file);

          $reader = Reader::createFromString($csvData);
          $reader->setHeaderOffset(0);
          $records = $reader->getRecords();

          $not_found = [];

          foreach ($records as $offset => $record) {
            switch ($provider_info['slug']) {
              case PaymentProviderEnum::GRABPAY:
                $provider = PaymentProvider::where('payment_provider_account_id', $record['merchant_ref'])->first();

                if ($provider) {
                  $cred = explode(':', $record['merchant_credentials']);
                  // Set credentials
                  $data = $provider->data;
                  $data['partner_id'] = $cred[0];
                  $data['partner_secret'] = $cred[1];
                  $data['merchant_id'] = $cred[2];
                  $provider->data = $data;

                  // Set status
                  $provider->onboarding_status = OnboardingStatus::SUCCESS;
                  $provider->save();
                } else {
                  $not_found[] = $record;
                }

                break;

              case PaymentProviderEnum::HOOLAH:

                break;
            }
          }

          if (count($not_found) > 0) {
            // store not in separate file
            $not_found_name = str_replace(basename($file), str_replace('.csv', '.not-found._csv', basename($file)), $file);

            $writer = Writer::createFromString('');
            $writer->insertOne($reader->getHeader());
            $writer->insertAll($not_found);

            Storage::disk('s3')->put($not_found_name, $writer->getContent());
          }

          // Rename original file
          // Replace ext
          $newFile = str_replace('.csv', '._csv', $file);

          // Rename file
          Storage::disk('s3')->move($file, $newFile);
        }
      }

      return 0;
    }
}
