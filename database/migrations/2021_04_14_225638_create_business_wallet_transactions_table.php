<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBusinessWalletTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('business_wallet_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->uuid('wallet_id');
            $table->string('event', 32);
            $table->uuid('related_wallet_id')->nullable();
            $table->bigInteger('balance_before');
            $table->bigInteger('amount');
            $table->bigInteger('balance_after');
            $table->nullableUuidMorphs('relatable');
            $table->tinyInteger('sequence')->nullable();
            $table->boolean('confirmed');
            $table->string('description')->nullable();
            $table->json('meta')->nullable();
            $table->json('timeline')->nullable();
            $table->timestamps();

            $table->index([
                'business_id',
                'wallet_id',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('business_wallet_transactions');
    }
}
