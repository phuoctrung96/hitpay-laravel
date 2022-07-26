<?php

namespace App\Console\Commands;

use App\Enumerations\BusinessPartnerStatus;
use App\Jobs\ChargePartnerCommissionJob;
use App\Models\BusinessPartner;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;

class ChargePartnersCommission extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'charge:partner-commission';

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
        $from = now()->subMonth()->startOfMonth();
        $to = now()->subMonth()->endOfMonth();
        if($to->format('Y-m') == '2021-09') {
            $from = Carbon::parse('2021-07-01');
        }

        BusinessPartner::query()
            ->where('status', BusinessPartnerStatus::ACCEPTED)
            ->has('businesses')
            ->each(function ($partner) use($from, $to) {
                dispatch(new ChargePartnerCommissionJob($partner, $from, $to));
            });

        return 0;
    }
}
