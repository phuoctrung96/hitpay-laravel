<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class PurgeRevokedSessions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hitpay:purge-revoked-sessions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description'; // todo

    /**
     * Execute the console command.
     */
    public function handle() : int
    {
        // todo
        return 0;
    }
}
