<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBusinessChargeAutoRefundsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('business_charge_auto_refunds', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->uuid('business_charge_id')->index();
            $table->uuid('business_payment_intent_id');
            $table->string('payment_provider', 32);
            $table->string('payment_provider_refund_type', 32)->nullable();
            $table->string('payment_provider_refund_id', 64)->nullable();
            $table->string('payment_provider_method', 32)->nullable();
            $table->char('currency', 3);
            $table->unsignedBigInteger('amount');
            $table->string('status', 32);
            $table->json('data')->nullable();
            $table->string('additional_reference')->nullable()->index();
            $table->timestamps();
            $table->timestamp('refunded_at')->nullable()->index();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('business_charge_auto_refunds');
    }
}
