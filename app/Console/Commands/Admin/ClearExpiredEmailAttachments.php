<?php

namespace App\Console\Commands\Admin;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Storage;

class ClearExpiredEmailAttachments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hitpay:clear-email-attachments';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear old email attachments';

    /**
     * Execute the console command.
     */
    public function handle() : int
    {
        $date = Date::today()->subWeek();

        Storage::disk('local')->deleteDirectory("email-attachments/{$date->toDateString()}");

        return 0;
    }
}
