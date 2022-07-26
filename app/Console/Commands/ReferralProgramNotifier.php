<?php

namespace App\Console\Commands;

use App\Business;
use App\Notifications\BusinessReferralProgramNotification;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

class ReferralProgramNotifier extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notify:referral-program';

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
    public function handle() : int
    {
        $this->getBusinesses()->each(function (Business $business) {
            $business->notify(new BusinessReferralProgramNotification());
        });

        return 0;
    }

    private function getBusinesses(): Builder
    {
        return Business::query()
            ->has('charges');
    }
}
