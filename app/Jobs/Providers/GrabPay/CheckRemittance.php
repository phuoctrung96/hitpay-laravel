<?php

namespace App\Jobs\Providers\GrabPay;

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
      $today = (new \DateTime())->format('Y-m-d'); 

      $rootPath = 'grabpay/remittance';
      $processedPath = $rootPath . '/processed/' . $today;
      $notFoundPath = $rootPath . '/not-found/' . $today;

      $files = array_filter(Storage::disk('s3')->files($rootPath), function ($value) {
        return str_ends_with($value, '.csv');
      });        

      foreach ($files as $file) {
        $csvData = Storage::disk('s3')->get($file);

        $reader = Reader::createFromString($csvData);
        //$reader->setHeaderOffset(0);
        $reader->setDelimiter('|');
        $records = $reader->getRecords();

        $not_found = [];

        foreach ($records as $offset => $record) {
          if (count($record) >= 28) {
            // We need only Collect records
            if ($record[3] === 'Collect') {
              // Partner Ref ID 1 & Partner Ref ID 2 should have the value partnerTxID & partnerGroupTxID
              // passed during /charge/init call
              // Find payment intent/charge
              $paymentIntent = PaymentIntent::where([
                'payment_provider_object_type' => 'inward_credit_notification',
                'payment_provider_object_id' => $record[27]
              ])->first();

              if ($paymentIntent instanceof PaymentIntent &&
                  $paymentIntent->status === 'succeeded') {

                $charge = $paymentIntent->charge;

                // Additional checks
                // Grab Transaction ID field should have Grab txID returned during /charge/complete

                if ($charge instanceof Charge &&
                    $charge->status === ChargeStatus::SUCCEEDED &&
                    $charge->data['grabpay_transaction_sn'] === $record[19]) {

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
          }
        }


        if (count($not_found) > 0) {
          $writer = Writer::createFromString('');
          $writer->insertOne($reader->getHeader());
          $writer->insertAll($not_found);

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
