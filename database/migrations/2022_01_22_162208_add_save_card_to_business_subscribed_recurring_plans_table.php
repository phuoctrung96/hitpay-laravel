<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSaveCardToBusinessSubscribedRecurringPlansTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('business_subscribed_recurring_plans', function (Blueprint $table) {
            $table->boolean('save_card')->default(false);
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
            $table->dropColumn('save_card');
        });
    }
}
