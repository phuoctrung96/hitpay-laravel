<?php

namespace App\Console\Commands;

use App\Business\HotglueJob;
use App\Business\HotglueIntegration;
use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Artisan;

class PullHotglueScheduledJobs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hotglue:pull-scheduled-jobs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Pull hotglue scheduled jobs';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        Log::info('Hotglue pull scheduled jobs start process');
        $client = new Client;
        $hotglueIntegrations = HotglueIntegration::whereFlow(config('services.hotglue.ecommerce_flow_id'))->whereConnected(true)->get();
        if ($hotglueIntegrations) {
            Log::info('Hotglue pull scheduled jobs ' . $hotglueIntegrations->count() . ' tenants to process');
            foreach ($hotglueIntegrations as $hotglueIntegration) {
                $url = config('services.hotglue.api_host') . '/' . config('services.hotglue.env_id') . '/' . $hotglueIntegration->flow . '/' . $hotglueIntegration->business_id . '/jobs';
                $response = $client->get($url, [
                    'headers' => [
                        'Accept' => 'application/json',
                        'x-api-key'=> config('services.hotglue.secret_api_key')
                    ]
                ]);
                $scheduledJobs = json_decode((string) $response->getBody(), true);
                foreach ($scheduledJobs as $scheduledJob) {
                    $jobExists = HotglueJob::whereJobId($scheduledJob['job_id'])->first();
                    if (!$jobExists) {
                        Log::info('Hotglue pull scheduled job for job_id ' . $scheduledJob['job_id']);
                        HotglueJob::create([
                            'hotglue_integration_id' => $hotglueIntegration->id,
                            'job_id' => $scheduledJob['job_id'],
                            'job_name' => 'scheduled-job-' . $scheduledJob['flow_id'],
                            'status' => HotglueJob::QUEUED,
                            'aws_path' => $scheduledJob['s3_root']
                        ]);
                        Artisan::queue('proceed:hotglue-feed --job_id=' . $scheduledJob['job_id']);
                    }
                }
            }
        } 
        Log::info('Hotglue pull scheduled jobs end process');
    }
}
