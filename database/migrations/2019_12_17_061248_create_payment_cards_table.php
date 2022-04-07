<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePaymentCardsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('payment_cards', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id')->index('index_user_id');
            $table->string('payment_provider', 32);
            $table->string('payment_provider_customer_id', 64);
            $table->string('payment_provider_card_id', 64);
            $table->string('name', 64)->nullable();
            $table->string('brand', 32);
            $table->char('country', 2);
            $table->string('funding', 8);
            $table->string('fingerprint', 64)->nullable();
            $table->string('last_4', 4);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('payment_cards');
    }
}
