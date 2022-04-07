<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBusinessSubscribedRecurringPlansTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('business_subscribed_recurring_plans', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_recurring_plan_renewal_cycle_id');
            $table->uuid('business_id');
            $table->uuid('user_id')->nullable();
            $table->uuid('business_customer_id')->nullable();
            $table->string('customer_name')->nullable();
            $table->string('customer_email')->nullable();
            $table->string('customer_phone_number', 32)->nullable();
            $table->string('customer_street')->nullable();
            $table->string('customer_city')->nullable();
            $table->string('customer_state')->nullable();
            $table->string('customer_postal_code', 16)->nullable();
            $table->char('customer_country', 2)->nullable();
            $table->string('name', 128);
            $table->text('description')->nullable();
            $table->unsignedBigInteger('price');
            $table->text('remark')->nullable();
            $table->timestamps();
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('expires_at')->nullable();

            $table->index($columns = [
                'business_id',
                'id',
            ], _blueprint_hash_columns('index', $columns));

            $table->index($columns = [
                'business_id',
                'business_recurring_plan_renewal_cycle_id',
                'id',
            ], _blueprint_hash_columns('index', $columns));

            $table->index($columns = [
                'business_id',
                'business_customer_id',
            ], _blueprint_hash_columns('index', $columns));

            $table->index($columns = [
                'user_id',
                'id',
            ], _blueprint_hash_columns('index', $columns));
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('business_subscribed_recurring_plans');
    }
}
