<?php

namespace App\Jobs\Providers\Shopee;

use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use League\Csv\Reader;
use League\Csv\Writer;
use App\Business\Charge;
use App\Business\PaymentIntent;
use App\Enumerations\Business\ChargeStatus;

class CheckRemittance
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     */
    public function handle() : void
    {
      $rootPath = 'shopee/remittance';
      $processedPath = $rootPath . '/processed';
      $notFoundPath = $rootPath . '/not-found';

      $files = array_filter(Storage::disk('s3')->files($rootPath), function ($value) {
        return str_ends_with($value, '.csv');
      });        

      foreach ($files as $file) {
        $csvData = Storage::disk('s3')->get($file);

        $reader = Reader::createFromString($csvData);
        $reader->setHeaderOffset(0);
        $records = $reader->getRecords();

        $not_found = [];

        foreach ($records as $offset => $record) {
          $paymentIntent = PaymentIntent::where([
            'payment_provider_object_type' => 'inward_credit_notification',            
            'payment_provider_object_id' => $record['External reference ID']
          ])->first();

          if ($paymentIntent instanceof PaymentIntent && $paymentIntent->status === 'succeeded') {
            $charge = $paymentIntent->charge;

            if ($charge && $charge->status === ChargeStatus::SUCCEEDED) {
              // same code as in canRefund
              $transaction = $charge->walletTransactions()->first();

                // check if charge already confirmed
              if (!($transaction && $transaction->confirmed)) {
                // confirm charge
                $charge->business->confirmCharge($charge);
              }
            } else {
              $not_found[] = $record;
            }  
          } else {
            $not_found[] = $record;
          }
        }

        if (count($not_found) > 0) {
          // store not in separate file
          //$not_found_name = str_replace(basename($file), str_replace('.csv', '.' . time() . '.not-found._csv', basename($file)), $file);

          $writer = Writer::createFromString('');
          $writer->insertOne($reader->getHeader());
          $writer->insertAll($not_found);

          //Storage::disk('s3')->put($not_found_name, $writer->getContent());

          if (!Storage::exists($notFoundPath)){
            Storage::makeDirectory($notFoundPath);
          }  

          $not_found_name = $notFoundPath . '/' . str_replace('.csv', '.' . time() . '.csv', basename($file));

          Storage::disk('s3')->put($not_found_name, $writer->getContent());
        }

        if (!Storage::exists($processedPath)){
          Storage::makeDirectory($processedPath);
        }

        $newFilename = $processedPath . '/' . basename($file);

        // Rename file
        Storage::disk('s3')->move($file, $newFilename);
      }
    }
}
