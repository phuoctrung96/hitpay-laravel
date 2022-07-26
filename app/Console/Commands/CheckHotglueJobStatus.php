<?php

namespace App\Console\Commands;

use App\Business;
use App\Business\HotglueJob;
use App\Business\HotglueIntegration;
use App\Notifications\HotglueSyncNotification;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Artisan;
use Throwable;

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
    public function handle() : int
    {
        Log::info('Hotglue job status checker start process');
        $client = new Client;

        DB::table('hotglue_jobs as hj')
            ->join('hotglue_integrations as hi', 'hi.id', '=', 'hj.hotglue_integration_id')
            ->select('hj.job_id', 'hj.job_name', 'hj.aws_path', 'hi.id', 'hi.type', 'hi.source', 'hi.flow', 'hi.business_id')
            ->where('status', HotglueJob::CREATED)
            ->whereIn('job_type', [HotglueJob::INITIAL_SYNC, HotglueJob::NOW_SYNC])
            ->chunkById(self::CHUNK_BY, function ($rows) use ($client) {
                Log::info('Hotglue job status checker ' . count($rows) . ' jobs to process');

                if ($rows) {
                    foreach ($rows as $row) {
                        /** @var HotglueJob $hotglueJob */
                        $hotglueJob = HotglueJob::where('job_id', $row->job_id)->first();
                        try {
                            $url = config('services.hotglue.api_host') . '/' . config('services.hotglue.env_id') . '/' . $row->flow . '/' . $row->business_id . '/jobs/status';
                            $response = $client->get($url, [
                                'query' => ['job_root' => $row->aws_path],
                                'headers' => [
                                    'Accept' => 'application/json',
                                    'x-api-key'=> config('services.hotglue.public_api_key')
                                ]
                            ]);
                            $response = json_decode((string) $response->getBody(), true);
                        } catch (ClientException $e) {
                            $response = json_decode((string) $e->getResponse()->getBody(), true);
                            $hotglueJob->status = HotglueJob::INVALID;
                            $hotglueJob->update();
                            Log::critical("Hotglue job error_code: {$response['Code']} error_message: {$response['Message']}");
                            continue;
                        }

                        if ($business = Business::find($row->business_id)) {
                            if ($row->type === HotglueJob::ECOMMERCE) {
                                $from = $row->source;
                                $to = 'hitpay';
                            } else {
                                $from = 'hitpay';
                                $to = $row->source;
                            }

                            if (strpos($response['status'], '_FAILED') !== false) {
                                $hotglueJob->status = HotglueJob::FAILED;
                                $hotglueJob->update();
                                $business->notify(new HotglueSyncNotification($from, $to, $hotglueJob->job_id));
                                Log::critical('Hotglue job failed to sync products from ' . $from . ' to ' . $to . ' per jobid: ' . $hotglueJob->job_id . ' for business_id: ' . $row->business_id);
                            } elseif ($response['status'] === HotglueJob::COMPLETED) {
                                if ($row->type === HotglueJob::ECOMMERCE) {
                                    $hotglueJob->status = HotglueJob::QUEUED;
                                    $hotglueJob->update();
                                    Log::info('Queued proceed:hotglue-feed --job_id=' . $row->job_id);
                                    try {
                                        Artisan::call('proceed:hotglue-feed --job_id='.$row->job_id);
                                    } catch (Throwable $exception) {
                                        $hotglueJob->status = HotglueJob::FAILED;
                                        $hotglueJob->update();

                                        Log::critical("Command run for `proceed:hotglue-feed` for Job ID ({$row->job_id}) has failed. Error : {$exception->getMessage()} at {$exception->getFile()}:{$exception->getLine()}\n{$exception->getTraceAsString()}");
                                    }
                                } elseif (strpos($row->job_name, 'update-qty') !== false || strpos($row->job_name, 'sync-hitpay-order') !== false) {
                                    $hotglueJob->status = HotglueJob::SYNCED;
                                    $hotglueJob->sync_date = now();
                                    $hotglueJob->update();
                                    Log::info('Hotglue job successfully added orders / updated product quantity from hitpay to ' . $row->source . ' per jobid: ' . $hotglueJob->job_id . ' for business_id: ' . $row->business_id);
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

        return 0;
    }
}
