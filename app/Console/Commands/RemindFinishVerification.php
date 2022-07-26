<?php

namespace App\Console\Commands;

use App\Business;
use App\Notifications\RemindFinishVerification as RemindFinishVerificationNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;

class RemindFinishVerification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'verification:remind';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send remind to complete verification';

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
        if (App::environment('production')) {
            $businesses = Business::where('verified_wit_my_info_sg', false)->whereNull('deleted_at')->has('paymentProviders')->get();

            foreach ($businesses as $business) {
                $business->notify(new RemindFinishVerificationNotification());
            }
        }

        return 0;
    }
}
