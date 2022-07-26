<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades;
use Illuminate\Support\Str;
use League\Flysystem\FileExistsException;
use Throwable;

class DoReconcileWithDBSAccount extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'do:dbs:reconcile';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Do reconcile DBS bank statement when bank statements are found.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle() : int
    {
        $running = Facades\Cache::driver('file')->get('reconciliation_is_running');

        if (!is_null($running)) {
            Facades\Log::driver('single')->info("The reconciliation '{$running['filename']}' is still running.");

            return 1;
        }

        $path = 'reconciliations/dbs/waiting-list';

        $files = Facades\Storage::files($path);
        $filename = Collection::make($files)
            ->filter(function (string $path) : bool {
                return Str::endsWith($path, '.csv');
            })
            ->sort()
            ->first();

        if (is_null($filename)) {
            return 1;
        }

        $_filename = str_replace("{$path}/", '', $filename);
        $_filename = str_replace(".csv", '', $_filename);

        $_originalFilename = $_filename;

        $moved = false;
        $counter = 0;

        do {
            try {
                $moved = Facades\Storage::move($filename, "reconciliations/dbs/raw/{$_filename}.csv");
            } catch (FileExistsException $exception) {
                $counter++;

                $_filename = "{$_originalFilename}-{$counter}";
            }
        } while (!$moved);

        $filename = $_filename;

        Facades\Log::driver('single')->info("The reconciliation '{$filename}' is started.");

        Facades\Cache::driver('file')->put('reconciliation_is_running', [
            'filename' => $filename,
        ]);

        try {
            Facades\Artisan::call("dbs:reconcile {$filename}");
        } catch (Throwable $throwable) {
            Facades\Log::driver('single')->error(
                "The reconciliation '{$filename}' is failed.\n{$throwable->getTraceAsString()}"
            );

            Facades\Cache::driver('file')->forget('reconciliation_is_running');

            return 1;
        }

        Facades\Log::driver('single')->info("The reconciliation '{$filename}' is ended.");

        Facades\Cache::driver('file')->forget('reconciliation_is_running');

        return 0;
    }
}
