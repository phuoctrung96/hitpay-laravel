<?php

use App\Business;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class SetNewDefaultReferralFee extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \Illuminate\Support\Facades\DB::statement('ALTER TABLE `business_referrals` CHANGE `referral_fee` `referral_fee` DOUBLE(9,4) NULL DEFAULT "0.10"');
        Business::query()->each(function (Business $business) {
            if($business->businessReferral) {
                $business->businessReferral()->update([
                    'referral_fee' => config('business.referral.default_fee')
                ]);
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
