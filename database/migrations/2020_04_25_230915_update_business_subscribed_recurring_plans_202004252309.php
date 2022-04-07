<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateBusinessSubscribedRecurringPlans202004252309 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('business_subscribed_recurring_plans', function (Blueprint $table) {
            $table->string('failed_reason', 64)->nullable()->after('status');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('business_subscribed_recurring_plans', function (Blueprint $table) {
            $table->dropColumn('failed_reason');
        });
    }
}
