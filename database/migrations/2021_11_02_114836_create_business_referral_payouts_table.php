<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBusinessReferralPayoutsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('business_referral_payouts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->uuid('referred_business_id');
            $table->unsignedBigInteger('transaction_amount');
            $table->unsignedBigInteger('referral_fee');
            $table->boolean('paid_status');
            $table->string('currency');
            $table->timestamps();

            $table->index(['business_id', 'paid_status']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('business_referral_payouts');
    }
}
