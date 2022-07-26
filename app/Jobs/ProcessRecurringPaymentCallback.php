<?php

namespace App\Jobs;

use App\Business\Charge;
use App\Business\RecurringBilling;
use App\Enumerations\Business\PluginProvider;
use App\Enumerations\Business\RecurringBillingEvent;
use App\Manager\ChargeManager;
use Illuminate\Support\Facades\Log;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use GuzzleHttp\Client;

use PDF;

class ProcessRecurringPaymentCallback implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $recurringBilling;

    public $charge;

    public $chargeStatus;

    public $event;

    /**
     * Create a new job instance.
     *
     * @param \App\Business\RecurringBilling $recurringBilling
     */
    public function __construct(RecurringBilling $recurringBilling, $event, $chargeStatus = null, $charge = null)
    {
        $this->chargeStatus = $chargeStatus;
        $this->recurringBilling = $recurringBilling;
        $this->charge = $charge;
        $this->event = $event;
    }

    /**
     * Execute the job.
     *
     * @throws \ReflectionException
     */
    public function handle()
    {
        if ($this->recurringBilling->webhook) {
            $chargeManager = new ChargeManager();
            $apiKey = $this->recurringBilling->business->apiKeys()->first();

            if ($this->event == RecurringBillingEvent::CHARGE_SUCCESS && $this->charge) {

                $signature = $chargeManager->generateSignatureArray($apiKey->salt, [
                    'payment_id' => $this->charge->getKey(),
                    'recurring_billing_id' => $this->recurringBilling->getkey(),
                    'amount' => getReadableAmountByCurrency($this->charge->currency, $this->charge->amount),
                    'currency' => $this->charge->currency,
                    'status' => $this->charge->status,
                    'reference' => $this->recurringBilling->reference,
                ]);

                $params = [
                    'payment_id' => $this->charge->getKey(),
                    'recurring_billing_id' => $this->recurringBilling->getkey(),
                    'amount' => getReadableAmountByCurrency($this->charge->currency, $this->charge->amount),
                    'currency' => $this->charge->currency,
                    'status' => $this->charge->status,
                    'reference' => $this->recurringBilling->reference,
                    'hmac' => $signature
                ];

                $client = new Client();

                $headers['User-Agent'] = 'HitPay v1.0';

                $response = $client->request('POST', $this->recurringBilling->webhook, [
                    'form_params' => $params,
                    'headers' => $headers
                ]);

                if ($response->getStatusCode() === 200) {
                    $this->recurringBilling->markAsSuccessfulPluginCallback();
                    return true;
                }
            }elseif ($this->event == RecurringBillingEvent::RECURRENT_BILLING_STATUS){
                $params = [
                    'recurring_billing_id' => $this->recurringBilling->getkey(),
                    'status' => $this->recurringBilling->status,
                    'amount' => getReadableAmountByCurrency($this->recurringBilling->currency, $this->recurringBilling->price),
                    'currency' => $this->recurringBilling->currency,
                    'reference' => $this->recurringBilling->reference,
                ];
                if ($this->chargeStatus)
                    $params['charge_status'] = $this->chargeStatus;

                $signature = $chargeManager->generateSignatureArray($apiKey->salt, $params);

                $params['hmac'] = $signature;

                $client = new Client();

                $headers['User-Agent'] = 'HitPay v1.0';

                $response = $client->request('POST', $this->recurringBilling->webhook, [
                    'form_params' => $params,
                    'headers' => $headers
                ]);

                if ($response->getStatusCode() === 200) {
                    $this->recurringBilling->markAsSuccessfulPluginCallback();
                    return true;
                }
            }
        }
    }
}
