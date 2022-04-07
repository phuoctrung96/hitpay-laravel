<?php

namespace App\Console\Commands;

use App\Business;
use App\Notifications\NotifyVerificationDocumentRequired;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class VerificationDocumentRequired extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'account:verification-document-required';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove verification for individual businesses who do not have any documents attached.';

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
    public function handle()
    {
        $businesses = Business::query()
            ->with('verifications')
            ->where('verified_wit_my_info_sg', '=', '1')
            ->whereHas('verifications', function($query) {
                return $query->where('type', '=', 'personal')
                    ->whereNull('supporting_documents');
            })
            ->get();

        foreach ($businesses as $business) {

            // personal type order first
            $businessVerifications = $business->verifications->sortByDesc('type');

            try {
                DB::beginTransaction();

                $isVerifiedBusinessType = false;

                foreach ($businessVerifications as $businessVerification) {
                    if ($businessVerification->type == 'business' && $businessVerification->verified_at != "") {
                        $isVerifiedBusinessType = true;

                        break;
                    } else {
                        // current database this verification no have softdelete
                        $businessVerification->delete();
                    }
                }

                if (!$isVerifiedBusinessType) {
                    $business->verified_wit_my_info_sg = 0;

                    $business->save();

                    // send email
                    $business->notify(new NotifyVerificationDocumentRequired());

                    $this->info('success send email to business id: ' . $business->id);
                }

                DB::commit();
            } catch (\Throwable $exception) {
                DB::rollBack();
                $this->info('error send email to business id: ' . $business->id);
                $this->info('error message: ' . $exception->getMessage());
            }
        }
    }
}
