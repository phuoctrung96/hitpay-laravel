<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateBusinessSubscribedRecurringPlans202102141950 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('business_subscribed_recurring_plans', function (Blueprint $table) {
            $table->string('dbs_dda_reference', 36)->nullable()->unique()->after('id');
            $table->string('payment_provider', 32)->nullable()->after('dbs_dda_reference');
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
            $table->dropColumn('payment_provider');
            $table->dropColumn('dbs_dda_reference');
        });
    }
}
