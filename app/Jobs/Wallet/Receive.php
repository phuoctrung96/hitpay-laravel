<?php

namespace App\Jobs\Wallet;

use App\Business\Charge;
use App\Exceptions\WalletException;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Enumerations;

class Receive
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var \App\Business\Charge
     */
    public $charge;

    /**
     * Create a new job instance.
     */
    public function __construct(Charge $charge)
    {
        $this->charge = $charge;
    }

    /**
     * Execute the job.
     */
    public function handle() : void
    {
        if ($this->charge->payment_provider_transfer_type === 'wallet') {
          try {
            switch ($this->charge->payment_provider) {
              case Enumerations\PaymentProvider::DBS_SINGAPORE:
                if (Str::startsWith($this->charge->payment_provider_charge_id, 'DICN') || $this->charge->payment_provider_charge_method === 'direct_debit') {
                  $confirmNow = true;
                }

                break;              
              
              case Enumerations\PaymentProvider::SHOPEE_PAY:
              default:
                $confirmNow = false;
                break;
            }

            $this->charge->business->receiveFromCharge($this->charge, $confirmNow);
          } catch (WalletException $exception) {
              Log::error($exception->getMessage());
          }            
        }
    }
}
