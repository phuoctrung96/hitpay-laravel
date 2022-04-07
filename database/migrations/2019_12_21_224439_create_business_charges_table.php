<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBusinessChargesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('business_charges', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->string('channel', 16);
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
            $table->string('payment_provider', 32)->nullable();
            $table->string('payment_provider_account_id', 64)->nullable();
            $table->string('payment_provider_charge_id', 64)->nullable();
            $table->string('payment_provider_charge_type', 32)->nullable(); // destination or direct
            $table->string('payment_provider_charge_method', 32)->nullable();
            $table->string('payment_provider_transfer_type', 32)->nullable(); // destination or direct
            $table->char('currency', 3);
            $table->unsignedBigInteger('amount');
            $table->char('home_currency', 3)->nullable();
            $table->unsignedBigInteger('home_currency_amount')->nullable();
            $table->decimal('exchange_rate', 20, 5)->nullable();
            $table->unsignedBigInteger('fixed_fee')->default(0);
            $table->unsignedBigInteger('discount_fee')->default(0);
            $table->decimal('discount_fee_rate', 5, 4)->default(0);
            $table->string('remark')->nullable();
            $table->string('business_target_type')->nullable();
            $table->uuid('business_target_id')->nullable();
            $table->string('status', 32);
            $table->string('failed_reason', 64)->nullable();
            $table->json('data')->nullable();
            $table->uuid('executor_id')->nullable();
            $table->ipAddress('request_ip_address');
            $table->string('request_user_agent', 1024)->nullable();
            $table->string('request_method', 8)->nullable();
            $table->string('request_url', 1024)->nullable();
            $table->char('request_country', 2)->nullable();
            $table->json('request_data')->nullable();
            $table->timestamps();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('closed_at')->nullable();

            $table->index($columns = [
                'business_id',
                'id',
            ], _blueprint_hash_columns('index', $columns));

            $table->index($columns = [
                'business_id',
                'business_customer_id',
            ], _blueprint_hash_columns('index', $columns));

            $table->index($columns = [
                'business_target_type',
                'business_target_id',
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
        Schema::dropIfExists('business_charges');
    }
}
