<?php

namespace App\Http\Middleware;

use Carbon\Carbon;
use Closure;
use DateTime;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class LogAccess
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     *
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        switch (true) {

            case Str::startsWith($request->path(), [
                'horizon',
                'storage',
            ]):
                return $next($request);
        }

        try {
            DB::enableQueryLog();

            $history[] = 'Date Time             : ' . Carbon::now()->toDateTimeString();
            $history[] = 'Request URI           : ' . $request->getUri();
            $history[] = 'Request Path          : ' . $request->path();
            $history[] = 'Request Method        : ' . $request->method();
            $history[] = 'Request Scheme        : ' . $request->getScheme();
            $history[] = 'IP Address            : ' . $request->ip();
            $history[] = 'User Agent            : ' . $request->userAgent();
            $history[] = 'HTTP Accept           : ' . implode(', ', $request->getAcceptableContentTypes());
            $history[] = 'HTTP Content Type     : ' . $request->getContentType();
            $history[] = 'Authorization         : ' . $request->bearerToken();

            if (count($requestData = $request->input())) {
                $history[] = 'Request Data          :';
                $history[] = null;

                $requestDataCount = 0;

                foreach ($requestData as $key => $value) {
                    $requestDataCount++;

                    $value = is_array($value) ? json_encode($value) : $value;

                    $history[] = $this->numbering($requestDataCount) . $key . ' => ' . $value;
                }

                $history[] = null;
            }

            if (count($fileData = $request->allFiles())) {
                $history[] = 'Request File          :';
                $history[] = null;

                foreach ($fileData as $key => $value) {
                    if (is_array($value)) {
                        foreach ($value as $file) {
                            /** @var \Illuminate\Http\UploadedFile $value */
                            $history[] = '    Filename => ' . $file->getClientOriginalName();
                            $history[] = '        Extension => ' . $file->extension();
                            $history[] = '        Mime Type => ' . $file->getMimeType();
                            $history[] = '        Size => ' . $file->getSize() . ' bytes';
                        }
                    } else {
                        /** @var \Illuminate\Http\UploadedFile $value */
                        $history[] = '    Filename => ' . $value->getClientOriginalName();
                        $history[] = '        Extension => ' . $value->extension();
                        $history[] = '        Mime Type => ' . $value->getMimeType();
                        $history[] = '        Size => ' . $value->getSize() . ' bytes';
                    }
                }

                $history[] = null;
            }
        } catch (Exception $exception) {
            Log::info($exception->getFile() . ':' . $exception->getLine() . ' => ' . $exception->getMessage() . "\n"
                . $exception->getTraceAsString());
        }

        $startedAt = microtime(true);

        $response = $next($request);

        try {
            $timeTaken = microtime(true) - $startedAt;
            $queryLogs = DB::getQueryLog();

            if (count($queryLogs)) {
                $history[] = 'Queries               :';
                $history[] = null;

                $queriesCount = 0;

                foreach ($queryLogs as $log) {
                    $queriesCount++;

                    $log['bindings'] = array_map(function ($value) {
                        if ($value instanceof DateTime) {
                            return $value->format(Carbon::DEFAULT_TO_STRING_FORMAT);
                        }

                        return $value;
                    }, $log['bindings']);

                    $history[] =
                        $this->numbering($queriesCount) . Str::replaceArray('?', $log['bindings'], $log['query']);
                }

                $history[] = null;
            }

            if ($response instanceof Response) {
                $history[] = 'Response Status Code  : ' . $response->getStatusCode();

                if ($response instanceof JsonResponse) {
                    $history[] = 'Response Data         :';
                    $history[] = null;
                    $history[] = $response->getContent();
                    $history[] = null;
                }
            }

            $history[] = 'Time Taken            : ' . $timeTaken;

            $user = optional($request->user());

            $history[] = 'User ID               : ' . $user->id;
            $history[] = 'User Name             : ' . $user->name;

            $business = optional($request->route('business', $request->route('business_id')));

            $history[] = 'Business ID           : ' . $business->id;
            $history[] = 'Business Name         : ' . $business->name;

            $sessionId = null;

            if (!$user->id) {
                $sessionId = $user->id;
            }

            if (!$sessionId) {
                $sessionId = optional($request->getSession())->getId();
            }

            if (!$sessionId) {
                $sessionId = 'unknown';
            }

            $directory = 'access-logs' . DIRECTORY_SEPARATOR . Date::now()->toDateString();
            $filename = $request->ip() . '-' . $sessionId . DIRECTORY_SEPARATOR . $startedAt . '.txt';

            Storage::put($directory . DIRECTORY_SEPARATOR . $filename, implode("\n", $history));
        } catch (Exception $exception) {
            Log::info($exception->getFile() . ':' . $exception->getLine() . ' => ' . $exception->getMessage() . "\n"
                . $exception->getTraceAsString());
        }

        return $response;
    }

    private function numbering(int $number)
    {
        return str_pad($number, 10, ' ', STR_PAD_LEFT) . '. ';
    }
}
