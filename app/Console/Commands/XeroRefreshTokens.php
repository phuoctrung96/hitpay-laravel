<?php

namespace App\Console\Commands;

use App\Services\Xero\RefreshTokensService;
use Illuminate\Console\Command;

class XeroRefreshTokens extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'xero:refresh-tokens';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
    public function handle(RefreshTokensService $refreshTokensService) : int
    {
        $refreshTokensService->handle();

        return 0;
    }
}
