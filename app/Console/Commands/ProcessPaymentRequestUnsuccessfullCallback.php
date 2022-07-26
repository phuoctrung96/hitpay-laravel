<?php

namespace App\Console\Commands;

use App\Business\PaymentRequest;
use App\Business\Charge;
use App\Manager\ChargeManagerInterface;
use App\Manager\PaymentRequestManagerInterface;
use App\Enumerations\Business\PluginProvider;
use App\Notifications\PaymentRequestFailedCallback;
use App\Enumerations\Business\ChargeStatus;
use Laravel\Passport\ClientRepository;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Notification;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

class ProcessPaymentRequestUnsuccessfullCallback extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hitpay:process-payment-request-unsuccessful-callback';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process payment requests unsuccessful order confirmation from merchants';

    /**
     * @var ChargeManagerInterface
     */
    private $chargeManager;

    /**
     * @var PaymentRequestManagerInterface
     */
    private $paymentRequestManager;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(ChargeManagerInterface $chargeManager, PaymentRequestManagerInterface $paymentRequestManager)
    {
        parent::__construct();

        $this->chargeManager            = $chargeManager;
        $this->paymentRequestManager    = $paymentRequestManager;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle() : int
    {
        if(Cache::has(md5($this->signature))) {
            // Skipped task as it's already running
            return 0;
        }

        Cache::put(md5($this->signature), true, 120);

        $charges = $this->chargeManager->getFindChargesForUnsucessfulPaymentRequestsCallback();

        $this->output->newLine();
        $this->comment('======================    <info>START PROCESSING CHARGES</info>    ======================');
        $this->output->newLine();

        foreach ($charges as $charge) {
            try {
                $this->comment(sprintf('<info>Processing Charge ID %s</info>', $charge->getKey()));
                $this->output->newLine();

                Log::info(sprintf('[PLUGIN-CALLBACK]: Processing Charge ID %s', $charge->getKey()));

                if (empty($charge->plugin_provider_reference)) {
                    Log::error('Empty \'plugin_provider_reference\', this is garbage, too much will slow down system.');

                    continue;
                }

                $data           = $charge->plugin_data;
                $paymentRequest = $this->paymentRequestManager->getFind($charge->plugin_provider_reference);

                if (!$paymentRequest instanceof PaymentRequest) {
                    continue;
                }

                if (!isset($data['url_callback']) || empty($data['url_callback'])) {
                    $this->comment(sprintf('<info>No callback configured. Charge ID: %s</info>', $charge->getKey()));
                    $this->output->newLine();

                    $this->chargeManager->markAsSuccessfulPluginCallback($charge);

                    if (!$paymentRequest->allow_repeated_payments) {
                        $this->paymentRequestManager->markAsCompleted($paymentRequest);
                    }
                    continue;
                }

                try {
                    $success = $this->pluginCallback($charge, $paymentRequest);
                } catch (ClientException $exception) {
                    $this->chargeManager->incrementRetryCount($charge);

                    if ($charge->callback_url_retry_count >= 3) {
                        Log::error('Callback failed max attempts for charge ID '.$charge->getKey());
                        $this->chargeManager->markAsFailedPluginCallback($charge);
                    }

                    Notification::route('slack', Config::get('services.slack.failed_callbacks'))
                        ->notify(new PaymentRequestFailedCallback(
                                $charge,
                                [
                                    'reference_number' => $paymentRequest->reference_number,
                                ],
                                $data['url_callback'] ?? 'unknown callback url',
                                $exception->getResponse()->getBody()->getContents())
                        );
                }

                if ($success) {
                    $this->chargeManager->markAsSuccessfulPluginCallback($charge);

                    if (!$paymentRequest->allow_repeated_payments) {
                        $this->paymentRequestManager->markAsCompleted($paymentRequest);
                    }

                    $this->comment(sprintf('<info>Processed Charge ID %s.</info>', $charge->getKey()));
                    $this->output->newLine();
                } else {
                    $this->chargeManager->incrementRetryCount($charge);

                    if ($charge->callback_url_retry_count >= 3) {
                        $this->chargeManager->markAsFailedPluginCallback($charge);
                    }

                    Log::info('Callback failed for charge ID '.$charge->getKey());
                }
            } catch (\Exception $e) {
                $this->chargeManager->incrementRetryCount($charge);

                if ($charge->callback_url_retry_count >= 3) {
                    $this->chargeManager->markAsFailedPluginCallback($charge);
                }

                Log::critical($e->getFile().':'.$e->getLine().' '.$e->getMessage()."\n".$e->getTraceAsString());
            }
        }

        $this->output->newLine();
        $this->comment('======================    <info>DONE PROCESSING</info>    ======================');
        $this->output->newLine();

        Cache::forget(md5($this->signature));

        return 0;
    }

    protected function pluginCallback(Charge $charge, PaymentRequest $paymentRequest)
    {
        try {
            $data   = $charge->plugin_data;
            $client = new Client();
            $apiKey = $charge->business->apiKeys()->first();


            if (!isset($data['url_callback']) || empty($data['url_callback'])) {
                return true;
            }

            $signature  = $this->chargeManager->generateSignatureArray($apiKey->salt, [
                'payment_id'                    => $charge->getKey(),
                'payment_request_id'            => $paymentRequest->getkey(),
                'phone'                         => '',
                'amount'                        => $data['amount'],
                'currency'                      => $data['currency'],
                'status'                        => 'completed',
                'reference_number'              => $paymentRequest->reference_number
            ]);

            $params = [
                'payment_id'                    => $charge->getKey(),
                'payment_request_id'            => $paymentRequest->getkey(),
                'phone'                         => '',
                'amount'                        => $data['amount'],
                'currency'                      => $data['currency'],
                'status'                        => 'completed',
                'reference_number'              => $paymentRequest->reference_number,
                'hmac'                          => $signature
            ];

            $response   = $client->request('POST', $data['url_callback'], [
                'form_params' => $params,
                'headers' => [
                    'User-Agent' => 'HitPay v1.0',
                ]
            ]);

            if ($response->getStatusCode() === 200) {
                return true;
            }

            $result     =  $response->getStatusCode();
        } catch (ClientException $e) {
            $response   = $e->getResponse();
            $result     = $response->getBody()->getContents();
            Log::info('Failed callback'.$e->getMessage());
        }  catch (\Exception $e) {
            $result     =  $e->getMessage();
            Log::info('Failed callback'.$e->getMessage());
        }

        $this->chargeManager->incrementRetryCount($charge);

        if ($charge->callback_url_retry_count >= 3) {
            $this->chargeManager->markAsFailedPluginCallback($charge);
        }

        Notification::route('slack', Config::get('services.slack.failed_callbacks'))
            ->notify(new PaymentRequestFailedCallback(
                $charge,
                $params,
                $data['url_callback'],
                $result
            ))
        ;

        return false;
    }
}
