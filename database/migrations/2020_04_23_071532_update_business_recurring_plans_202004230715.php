<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateBusinessRecurringPlans202004230715 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::drop('business_recurring_plan_renewal_cycles');

        Schema::table('business_recurring_plans', function (Blueprint $table) {
            $table->string('cycle', 32)->after('description');
            $table->unsignedBigInteger('price')->after('currency');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('business_recurring_plans', function (Blueprint $table) {
            $table->dropColumn('cycle');
            $table->dropColumn('price');
        });

        Schema::create('business_recurring_plan_renewal_cycles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_recurring_plan_id')->index('index_business_recurring_plan_id');
            $table->string('renewal_cycle', 32);
            $table->text('description')->nullable();
            $table->unsignedBigInteger('price');
            $table->boolean('active');
            $table->timestamps();

            $table->index($columns = [
                'business_recurring_plan_id',
                'id',
            ], _blueprint_hash_columns('index', $columns));
        });
    }
}
