<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessInwardCreditNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use Throwable;

class PayNowWebhookController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \Throwable
     */
    public function __invoke(Request $request)
    {
        $body = $request->getContent();

        $filename = md5($body);

        Storage::put('paynow-request'.DIRECTORY_SEPARATOR.$filename.'.txt', $body);

        ProcessInwardCreditNotification::dispatch($body, $filename);

        try {
            // We set 180 seconds TTL here, if the cache return empty later, means it already 3 minutes this API
            // isn't called.
            //
            Cache::put('last_paynow_callback', Date::now()->toDateTimeString('microsecond'), 180);
            Cache::put('last_paynow_callback_datetime', Date::now()->toDateTimeString('microsecond'));
        } catch (Throwable $throwable) {
            Log::critical('Setting last PayNow webhook indicator failed.');
        }

        return Response::json();
    }
}
