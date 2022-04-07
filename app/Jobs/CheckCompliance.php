<?php

namespace App\Jobs;

use App\Business\Verification;
use Illuminate\Support\Facades\Log;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use GuzzleHttp\Client;

use PDF;

class CheckCompliance implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $verification;

    /**
     * Create a new job instance.
     *
     * @param \App\Business\Verification $verification
     */
    public function __construct(Verification $verification)
    {
        $this->verification = $verification;
    }

    /**
     * Execute the job.
     *
     * @throws \ReflectionException
     */
    public function handle()
    {

        $api_key = config('services.comply_advantage.key');

        try {
            $client = new Client();

            if ($this->verification->type === 'business') {
                $search_terms = [$this->verification->submitted_data['name'], $this->verification->submitted_data['entity_name']];
                foreach ($shareholders = $this->verification->submitted_data['shareholders'] as $shareholder) {
                    $search_terms[] = $shareholder;
                }

                $client_ref = $this->verification->business_id;

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
                $client_ref = $this->verification->business_id;

                $response = $client->post('https://api.complyadvantage.com/searches?api_key=' . $api_key, [
                    'json' => [
                        'search_term' => $this->verification->submitted_data['name'],
                        'fuzziness' => 0.6,
                        'limit' => 10,
                        'client_ref' => $client_ref
                    ]
                ]);

                $results = json_decode((string)$response->getBody(), true);
            }

            return;


        } catch (Exception $exception) {
            Log::error($exception->getResponse()->getBody()->__toString());
        }
    }
}
