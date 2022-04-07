<?php

namespace App\Console\Commands;

use App\Business;
use App\Services\Xero\DisconnectService;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;

class XeroCleanupConnections extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'xero:clean-connections';

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
    public function handle(DisconnectService $disconnectService)
    {
        foreach ($this->getConnectedBusinesses() as $connectedBusiness) {
            if($disconnectService->isXeroConnectionDead($connectedBusiness)) {
                $disconnectService->disconnectBusinessFromXero($connectedBusiness);
            }
        }
    }

    private function getConnectedBusinesses(): Collection
    {
        return Business::query()
            ->whereRaw('LENGTH(xero_refresh_token) > 0')
            ->get();
    }
}
