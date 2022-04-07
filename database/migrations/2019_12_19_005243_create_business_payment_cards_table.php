<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBusinessPaymentCardsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('business_payment_cards', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->string('payment_provider', 32);
            $table->string('payment_provider_customer_id', 64)->nullable();
            $table->string('payment_provider_card_id', 64);
            $table->string('name', 64)->nullable();
            $table->string('brand', 32);
            $table->char('country', 2);
            $table->string('funding', 8);
            $table->string('fingerprint', 64)->nullable();
            $table->string('last_4', 4);
            $table->json('data')->nullable();
            $table->timestamps();

            $table->index($columns = [
                'business_id',
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
        Schema::dropIfExists('business_payment_cards');
    }
}
