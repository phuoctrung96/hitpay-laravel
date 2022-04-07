<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateBusinessSubscribedRecurringPlans202005280158 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('business_subscribed_recurring_plans', function (Blueprint $table) {
            $table->unsignedMediumInteger('times_to_be_charged')->nullable()->after('cycle');
            $table->unsignedMediumInteger('times_charged')->nullable()->after('times_to_be_charged');
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
            $table->dropColumn('times_charged');
            $table->dropColumn('times_to_be_charged');
        });
    }
}
