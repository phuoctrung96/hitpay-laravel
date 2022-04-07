<?php

namespace App\Console\Commands\Helpers;

use Carbon\Carbon;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Storage;

trait StoresLocalFile
{
    /**
     * Store a local file, default to "email-attachments" directory.
     *
     * @param  \Carbon\Carbon  $startDate
     * @param  \Carbon\Carbon|null  $endDate
     * @param  string  $path
     * @param  string  $filename
     * @param  string  $content
     */
    public function storeAndGroupByDate(
        Carbon $startDate, ?Carbon $endDate, string $path, string $filename, string $content
    ) : void {
        if ($endDate instanceof Carbon) {
            if ($startDate->isSameDay($endDate)) {
                $period = $startDate->toDateString();
            } else {
                $period = "{$startDate->toDateString()} to {$endDate->toDateString()}";
            }
        } else {
            $period = $startDate->toDateString();
        }

        $now = Date::now();

        $uniqueKey = $now->timestamp.str_pad($now->millisecond, 4, '0');

        if (property_exists($this, 'rootPath') && is_string($this->rootPath)) {
            $rootPath = $this->rootPath;
        } else {
            $rootPath = "email-attachments/{$now->toDateString()}";
        }

        $path = "{$rootPath}/{$path}/{$filename} - {$period} ({$uniqueKey}).csv";

        Storage::disk('local')->put(preg_replace('/[^0-9a-zA-Z.()\[\]\/@]+/', '-', $path), $content);
    }
}
