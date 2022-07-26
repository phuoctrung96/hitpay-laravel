<?php

namespace App\Console\Commands;

use App\Client as OauthClient;
use App\Business\Charge;
use App\Manager\ChargeManagerInterface;
use App\Enumerations\Business\PluginProvider;
use Laravel\Passport\ClientRepository;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

class ProcessGatewayUnsuccessfullCallback extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hitpay:process-gateway-unsuccessful-callback';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process payment gateways unsuccessful order confirmation from merchants';

    /**
     * @var ChargeManagerInterface
     */
    private $chargeManager;

    /**
     * @var ClientRepository
     */
    private $clientRepository;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(ChargeManagerInterface $chargeManager, ClientRepository $clientRepository)
    {
        parent::__construct();

        $this->chargeManager    = $chargeManager;
        $this->clientRepository = $clientRepository;
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

        $charges = $this->chargeManager->getFindChargesForUnsucessfulPaymentGateways();

        $this->output->newLine();
        $this->comment('======================    <info>START PROCESSING CHARGES</info>    ======================');
        $this->output->newLine();

        foreach ($charges as $charge) {
            try {
                $this->comment(sprintf('<info>Processing Charge ID %s</info>', $charge->getKey()));
                $this->output->newLine();

                Log::info(sprintf('[PLUGIN-CALLBACK]: Processing Charge ID %s', $charge->getKey()));

                $data = $charge->plugin_data;

                if (empty($data)) {
                    $this->comment(sprintf('<info>Unable to process. No plugin data.</info>', $charge->getKey()));
                    $this->output->newLine();

                    $this->chargeManager->markAsSuccessfulPluginCallback($charge);

                    continue;
                }

                if ($this->pluginCallback($charge)) {
                    $this->chargeManager->markAsSuccessfulPluginCallback($charge);

                    $this->comment(sprintf('<info>Processed Charge ID %s.</info>', $charge->getKey()));
                    $this->output->newLine();
                } else {
                    $this->chargeManager->incrementRetryCount($charge);

                    if ($charge->callback_url_retry_count >= 3) {
                        $this->chargeManager->markAsFailedPluginCallback($charge);
                    }
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

    protected function pluginCallback(Charge $charge)
    {
        try {
            $data           = $charge->plugin_data;
            $client         = new Client();
            $oauthClient    = $this->clientRepository->find($data['account_id']);

            if (!$oauthClient instanceof OauthClient) {
                Log::critical(sprintf('[PLUGIN-CALLBACK]: Client ID not found %s', $data['account_id']));

                return false;
            }

            $signature  = $this->chargeManager->generateSignatureArray($oauthClient->secret, [
                'x_account_id'          => $data['account_id'],
                'x_reference'           => $data['reference'],
                'x_currency'            => $data['currency'],
                'x_test'                => $data['test'],
                'x_amount'              => $data['amount'],
                'x_gateway_reference'   => $charge->getKey(),
                'x_timestamp'           => $data['timestamp'],
                'x_result'              => 'completed'
            ]);

            $params     = [
                'x_account_id'          => $data['account_id'],
                'x_amount'              => $data['amount'],
                'x_currency'            => $data['currency'],
                'x_gateway_reference'   => $charge->getKey(),
                'x_reference'           => $data['reference'],
                'x_signature'           => $signature, //$data['response_signature'],
                'x_test'                => $data['test'],
                'x_timestamp'           => $data['timestamp'],
                'x_result'              => 'completed'
            ];

            if ($data['plugin_provider'] === PluginProvider::WOOCOMMERCE) {
                $params['x_order_id']   = $data['order_id'];
            }

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
        }  catch (\Exception $e) {
            $result     =  $e->getMessage();
        }

        $this->chargeManager->incrementRetryCount($charge);

        if ($charge->callback_url_retry_count >= 3) {
            $this->chargeManager->markAsFailedPluginCallback($charge);
        }

        Log::critical(sprintf('[PLUGIN-CALLBACK]: Error Charge ID %s. Endpoint %s with data %s', $charge->getKey(), $data['url_callback'], json_encode($params)));
        Log::critical(sprintf('[PLUGIN-CALLBACK]: Result %s', $result));

        return false;
    }
}
