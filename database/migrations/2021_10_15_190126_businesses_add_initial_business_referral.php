<?php

use App\Business;
use App\Business\BusinessReferral;
use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Str;

class BusinessesAddInitialBusinessReferral extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Business::query()->each(function (Business $business) {
            if(!$business->businessReferral) {
                $business->businessReferral()->create([
                    'id' => Str::uuid(),
                    'code' => $this->generateCode($business),
                    'country' => $business->country,
                    'starts_at' => Carbon::now(),
                    'referral_fee' => config('business.referral.default_fee')
                ]);
            }
        });
    }

    private function generateCode(Business $business): string
    {
        $code = false;

        $generateCode = function (string $name) {
            $firstPart = Str::substr(str_replace(' ', '', $name), 0, 5);
            $secondPart = Str::random(5 + (5-strlen($firstPart)));
            $code = Str::upper($firstPart . $secondPart);

            if (BusinessReferral::where('code', $code)->exists()) {
                return false;
            }

            return $code;
        };

        while (!$code) {
            $code = $generateCode($business->name);
        }

        return $code;
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
