<?php

namespace App\Console\Commands;

use App\Business;
use App\Business\Refund;
use App\Enumerations\PaymentProvider;
use App\Jobs\Wallet\Refund as RefundJob;
use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CompliancePendingBusiness extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hitpay:verify-pending-businesses';


    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check pending businesses with verification API';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $verifications = Business\Verification::where('verified_at', '<', '2021-08-26 10:41:51')->get();

        $api_key = config('services.comply_advantage.key');

        try {
            $client = new Client();

            foreach ($verifications as $verification) {
                if ($verification->type === 'business') {
                    $search_terms = [$verification->my_info_data['data']['person']['name']['value'], $verification->my_info_data['data']['entity']['basic-profile']['entity-name']['value']];
                    foreach ($shareholders = $verification->my_info_data['data']['entity']['shareholders']['shareholders-list'] as $shareholder) {
                        $search_terms[] = $shareholder['person-reference']['person-name']['value'] ?? $shareholder['entity-reference']['entity-name']['value'];
                    }

                    $client_ref = $verification->business_id;

                    foreach ($search_terms as $term) {
                        $response = $client->post('https://api.complyadvantage.com/searches?api_key=' . $api_key, [
                            'json' => [
                                'search_term' => $term,
                                'fuzziness' => 0.6,
                                'limit' => 10,
                                'client_ref' => $client_ref
                            ]
                        ]);

                        $results = json_decode((string)$response->getBody(), true);
                    }
                } else {
                    $client_ref = $verification->business_id;

                    $response = $client->post('https://api.complyadvantage.com/searches?api_key=' . $api_key, [
                        'json' => [
                            'search_term' => $verification->my_info_data['data']['name']['value'],
                            'fuzziness' => 0.6,
                            'limit' => 10,
                            'client_ref' => $client_ref
                        ]
                    ]);

                    $results = json_decode((string)$response->getBody(), true);
                }
            }

            return;


        } catch (Exception $exception) {
            Log::error($exception->getResponse()->getBody()->__toString());
        }
    }
}
