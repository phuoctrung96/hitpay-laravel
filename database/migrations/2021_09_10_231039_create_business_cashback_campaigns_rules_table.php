<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBusinessCashbackCampaignsRulesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('business_cashback_campaigns_rules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('campaign_id');
            $table->uuid('business_id');
            $table->char('currency', 3);
            $table->decimal('min_spend', 7, 2)->default(0);
            $table->decimal('cashback_amt_fixed', 7, 2)->default(0);
            $table->decimal('cashback_amt_percent', 7, 2)->default(0);
            $table->decimal('maximum_cap', 7, 2)->default(0);
            $table->decimal('total_cashback', 7, 2)->default(0);
            $table->decimal('balance_cashback', 7, 2)->default(0);

            $table->timestamps();
            $table->foreign('campaign_id')->references('id')->on('business_cashback_campaigns')->onDelete('cascade');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('business_cashback_campaigns_rules');
    }
}
