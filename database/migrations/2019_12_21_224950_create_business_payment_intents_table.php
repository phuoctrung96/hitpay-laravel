<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBusinessPaymentIntentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('business_payment_intents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->uuid('business_charge_id')->nullable();
            $table->string('payment_provider', 32);
            $table->string('payment_provider_account_id', 64)->nullable();
            $table->string('payment_provider_object_type', 32);
            $table->string('payment_provider_object_id', 64);
            $table->string('payment_provider_method', 32)->nullable();
            $table->char('currency', 3);
            $table->unsignedBigInteger('amount');
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

            $table->index($columns = [
                'business_charge_id',
                'id',
            ], _blueprint_hash_columns('index', $columns));

            $table->index($columns = [
                'payment_provider_object_type',
                'payment_provider_object_id',
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
        Schema::dropIfExists('business_payment_intents');
    }
}
