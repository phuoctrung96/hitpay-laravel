<?php

namespace App\Jobs\Providers\Zip;

use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use App\Business\Charge;
use App\Business\PaymentIntent;
use Illuminate\Console\Command;
use App\Helpers\ZipOauth;
use Carbon\Carbon;
use App\Enumerations\Business\ChargeStatus;
use App\Enumerations\PaymentProvider as PaymentProviderEnum;

class CheckRemittance implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $from;
    public $to;

    public function __construct($from = null, $to = null)
    {
        $this->from = $from;
        $this->to = $to;
    }

    /**
     * Execute the job.
     */
    public function handle() : void
    {
      // If date is not defined process last 2 days
      if (!isset($this->from) || !isset($this->to)) {
        $dateTo = new Carbon();
        $dateFrom = Carbon::now()->subDays(2);

        $dateTo = $dateTo->toJSON();      
        $dateFrom = $dateFrom->toJSON(); 
      } else {
        $dateTo = $this->to;
        $dateFrom = $this->from;
      }

      $zip = new ZipOauth();

      $page = 1;
      $totalCount = -1;
      $maxPage = 0;
      $pageSize = 100;
      $chargesUrl = 'https://' . config('services.zip.api_merchant_url') . '/merchants/settlements';

      do {
        $charges = $zip->getRequest($chargesUrl, [
          'DateFrom' => $dateFrom,
          'DateTo' => $dateTo,
          'PageNumber' => $page,
          'PageSize' => $pageSize
        ]);

        // set total on first call
        if ($totalCount < 0) {
          $totalCount = $charges->totalCount;
          $maxPage = ceil($totalCount / $pageSize);
        }

        // process charges
        for ($i = 0; $i < count($charges->data); $i++) {
          $charge = $charges->data[$i];

          if ($charge->transactionAmount > 0) {
            // Charges
            $chargeRef = 'sg-' . $charge->merchantTransactionReference;

            $paymentIntent = PaymentIntent::where([
              'payment_provider_object_id' => $chargeRef,
              'payment_provider_object_type' => 'payment_intent',
              'payment_provider' => PaymentProviderEnum::ZIP    
            ])->first();

            if ($paymentIntent instanceof PaymentIntent) {
              //$chargeModel = Charge::where('payment_provider_charge_id', $chargeRef)->first();
              $chargeModel = Charge::find($paymentIntent->business_charge_id);
              
              if ($chargeModel instanceof Charge) {
                if ($chargeModel->status === ChargeStatus::SUCCEEDED) {
                  // Check if charge is not already confirmed
                  $transaction = $chargeModel->walletTransactions()->first();

                  if (!($transaction && $transaction->confirmed)) {
                    $chargeModel->business->confirmCharge($chargeModel);
                  }
                }
              } else {
                Log::error('[Zip] Charge with ref ' . $chargeRef . ' not found and can not be confirmed');
              }
            } else {
              Log::error('[Zip] Charge with ref ' . $chargeRef . ' not found and can not be confirmed');
            }
            
          } elseif ($charge->transactionAmount < 0) {
            // Refunds
          }
        }
  
        $page++;
      } while ($page <= $maxPage);
    }
}
