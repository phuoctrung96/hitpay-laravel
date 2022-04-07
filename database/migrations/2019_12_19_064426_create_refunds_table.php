<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRefundsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('refunds', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('charge_id');
            $table->string('payment_provider', 32);
            $table->string('payment_provider_account_id', 64)->nullable();
            $table->string('payment_provider_refund_id', 64)->nullable();
            $table->string('payment_provider_refund_type', 32)->nullable();
            $table->string('payment_provider_refund_method', 32);
            $table->unsignedBigInteger('amount');
            $table->string('remark')->nullable();
            $table->json('data')->nullable();
            $table->uuid('executor_id')->nullable();
            $table->ipAddress('request_ip_address');
            $table->string('request_user_agent', 1024)->nullable();
            $table->string('request_method', 8)->nullable();
            $table->string('request_url', 1024)->nullable();
            $table->char('request_country', 2)->nullable();
            $table->json('request_data')->nullable();
            $table->timestamps();

            $table->index($columns = [
                'charge_id',
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
        Schema::dropIfExists('refunds');
    }
}
