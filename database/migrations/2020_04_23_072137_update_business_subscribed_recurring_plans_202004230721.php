<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateBusinessSubscribedRecurringPlans202004230721 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('business_subscribed_recurring_plans', function (Blueprint $table) {
            $table->dropIndex(_blueprint_hash_columns('index', [
                'business_id',
                'business_recurring_plan_renewal_cycle_id',
                'id',
            ]));

            $table->dropColumn('business_recurring_plan_renewal_cycle_id');
            $table->string('payment_provider_customer_id', 64)->nullable()->after('id');
            $table->string('payment_provider_payment_method_id', 64)->nullable()->after('payment_provider_customer_id');
            $table->char('currency', 3)->after('description');
            $table->string('cycle', 32)->after('remark');
            $table->string('status', 32)->after('cycle');
            $table->json('data')->nullable()->after('status');
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
            $table->dropColumn('data');
            $table->dropColumn('status');
            $table->dropColumn('cycle');
            $table->dropColumn('currency');
            $table->dropColumn('pm_1GaxgdAMHowMCIhZaDyrH9k1');
            $table->dropColumn('payment_provider_customer_id');
            $table->uuid('business_recurring_plan_renewal_cycle_id')->after('id');

            $table->index($columns = [
                'business_id',
                'business_recurring_plan_renewal_cycle_id',
                'id',
            ], _blueprint_hash_columns('index', $columns));
        });
    }
}
