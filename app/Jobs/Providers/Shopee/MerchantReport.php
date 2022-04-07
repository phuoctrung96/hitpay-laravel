<?php

namespace App\Jobs\Providers\Shopee;

use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use League\Csv\Reader;
use League\Csv\Writer;
use Exception;

use App\Business\PaymentProvider;
use App\Enumerations\Business\ChargeStatus;

use App\Enumerations\PaymentProvider as PaymentProviderEnum;

class MerchantReport
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     */
    public function handle() : void
    {
      // 1. Get list of business providers
      $paymentProviders = PaymentProvider::where('payment_provider', PaymentProviderEnum::SHOPEE_PAY)
        ->where('reported', false)
        ->get();

      if (!$paymentProviders->isEmpty()) {
        // 2. Generate CSV
        $data = [];
        $csv = Writer::createFromString('');
        $csv->insertOne([
          'Merchant Name',
          'ext MID',
          'Store name',
          'ext SID',
          'UEN',
          'ACRA',
          'Logo',
          'Banner',
          'Outlet Full Address',
          'Postal Code',
        ]);

        DB::beginTransaction();

        try {
          foreach ($paymentProviders as $paymentProvider) {
            $business = $paymentProvider->business()->first();
    
            $data[] = [
              'Merchant Name' => $business->name,
              'ext MID' => $paymentProvider->payment_provider_account_id,
              'Store name' => $paymentProvider->data['store_name'],
              'ext SID' => $paymentProvider->data['sid'],
              'UEN' => $paymentProvider->data['company_uen'],
              'ACRA' => '',
              'Logo' => '',
              'Banner' => '',
              'Outlet Full Address' => $paymentProvider->data['city'] . ' ' . $paymentProvider->data['address'],
              'Postal Code' => $paymentProvider->data['postal_code'],
            ];
    
            $paymentProvider->reported = true;
            $paymentProvider->save();
          }
    
          $csv->insertAll($data);
    
          // 3. Save file to S3
          Storage::disk('s3')->put('shopee/merchant-report/SP_HP_' . date("YmdHis") . '.csv', $csv->getContent());  

          DB::commit();
        } catch (Exception $error) {
          DB::rollback();
          throw $error;
        }
      }
    }
}
