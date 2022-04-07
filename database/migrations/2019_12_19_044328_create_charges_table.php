<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateChargesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('charges', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('owner_type')->nullable();
            $table->uuid('owner_id')->nullable();
            $table->string('payment_provider', 32);
            $table->string('payment_provider_account_id', 64)->nullable();
            $table->string('payment_provider_charge_id', 64)->nullable();
            $table->string('payment_provider_charge_type', 32)->nullable();
            $table->string('payment_provider_charge_method', 32);
            $table->char('currency', 3);
            $table->unsignedBigInteger('amount');
            $table->char('home_currency', 3)->nullable();
            $table->unsignedBigInteger('home_currency_amount')->nullable();
            $table->decimal('exchange_rate', 20, 5)->nullable();
            $table->string('remark')->nullable();
            $table->string('target_type')->nullable();
            $table->uuid('target_id')->nullable();
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
            $table->timestamp('closed_at')->nullable();

            $table->index($columns = [
                'owner_type',
                'owner_id',
                'id',
            ], _blueprint_hash_columns('index', $columns));

            $table->index($columns = [
                'target_type',
                'target_id',
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
        Schema::dropIfExists('charges');
    }
}
