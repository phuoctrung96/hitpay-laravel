<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddWebhookBusinessSubscribedRecurringPlansTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('business_subscribed_recurring_plans', function (Blueprint $table) {
            $table->string('webhook')->nullable();
            $table->boolean('is_succeeded_webhook_callback')->default(false);
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
            $table->dropColumn('webhook');
            $table->dropColumn('webhook_callback_status');
        });
    }
}
