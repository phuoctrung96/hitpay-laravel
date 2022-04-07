<?php

namespace App\Console\Commands;

use App\Business;
use App\Manager\ApiKeyManager;
use Illuminate\Console\Command;

class CreateApiKeyForBusinesses extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hitpay:business-api-key';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create API key for businesses';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $count = Business::whereDoesntHave('apiKeys')->count();

        $this->comment($count.' businesses are without API key.');

        if ($count === 0 || !$this->confirm('Generate for them?')) {
            return;
        }

        Business::whereDoesntHave('apiKeys')->each(function (Business $business) {
            ApiKeyManager::create($business);
        });
    }
}
