<?php

namespace App\Console\Commands;

use App\Business;
use App\Business\HotglueJob;
use App\Business\HotglueIntegration;
use App\Notifications\HotglueSyncNotification;
use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Artisan;

class CheckHotglueJobStatus extends Command
{
    const CHUNK_BY = 100;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hotglue:check-job-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check hoglue job status';

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
        Log::info('Hotglue job status checker start process');
        $client = new Client;

        DB::table('hotglue_jobs as hj')
            ->join('hotglue_integrations as hi', 'hi.id', '=', 'hj.hotglue_integration_id')
            ->select('hj.job_id', 'hj.job_name', 'hj.aws_path', 'hi.id', 'hi.type', 'hi.source', 'hi.flow', 'hi.business_id')
            ->where('status', HotglueJob::CREATED)
            ->chunkById(self::CHUNK_BY, function ($rows) use ($client) {
                Log::info('Hotglue job status checker ' . count($rows) . ' jobs to process');

                if ($rows) {
                    foreach ($rows as $row) {
                        $url = config('services.hotglue.api_host') . '/' . config('services.hotglue.env_id') . '/' . $row->flow . '/' . $row->business_id . '/jobs/status';
                        $response = $client->get($url, [
                            'query' => ['job_root' => $row->aws_path],
                            'headers' => [
                                'Accept' => 'application/json',
                                'x-api-key'=> config('services.hotglue.public_api_key')
                            ]
                        ]);
                        $response = json_decode((string) $response->getBody(), true);

                        if ($hotglueJob = HotglueJob::whereJobId($response['job_id'])->first()) {
                            if (strpos($response['status'], '_FAILED') !== false) {
                                $hotglueJob->status = HotglueJob::FAILED;
                                $hotglueJob->update();
        
                                if ($row->type === HotglueJob::ECOMMERCE) {
                                    $from = $row->source;
                                    $to = 'hitpay';
                                } else {
                                    $from = 'hitpay';
                                    $to = $row->source;
                                }
                                $business = Business::find($row->business_id);
                                $business->notify(new HotglueSyncNotification($from, $to, $hotglueJob->job_id));
                                Log::critical('Hotglue job failed to sync products from ' . $from . ' to ' . $to . ' per jobid: ' . $hotglueJob->job_id . ' for business_id: ' . $row->business_id);
                            } elseif ($response['status'] === HotglueJob::COMPLETED) {
                                if ($row->type === HotglueJob::ECOMMERCE) {
                                    $hotglueJob->status = HotglueJob::QUEUED;
                                    $hotglueJob->update();
                                    Log::info('Queued proceed:hotglue-feed --job_id=' . $row->job_id);
                                    Artisan::queue('proceed:hotglue-feed --job_id=' . $row->job_id);
                                } elseif (strpos($row->job_name, 'update-qty') !== false) {
                                    $hotglueJob->status = HotglueJob::SYNCED;
                                    $hotglueJob->sync_date = now();
                                    $hotglueJob->update();
                                    Log::info('Hotglue job successfully updated product quantity from hitpay to ' . $row->source . ' per jobid: ' . $hotglueJob->job_id . ' for business_id: ' . $row->business_id);
                                } else {
                                    $hotglueIntegration = HotglueIntegration::find($row->id);
                                    if ($hotglueIntegration->initial_sync_date === null) {
                                        $hotglueIntegration->initial_sync_date = now();
                                    }
                                    $hotglueIntegration->last_sync_date = now();
                                    $hotglueIntegration->update();
        
                                    $hotglueJob->status = HotglueJob::SYNCED;
                                    $hotglueJob->sync_date = now();
                                    $hotglueJob->update();
                                    Log::info('Hotglue job successfully synced products from hitpay to ' . $row->source . ' per jobid: ' . $hotglueJob->job_id . ' for business_id: ' . $row->business_id);
                                }
                            }
                        }
                    }
                }
        });

        Log::info('Hotglue job status checker end process');
    }
}
