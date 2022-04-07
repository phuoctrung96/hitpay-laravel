<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateBusinessPaymentIntents202110271444 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('business_payment_intents', function (Blueprint $table) {
            $table->index('additional_reference');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('business_payment_intents', function (Blueprint $table) {
            $table->dropIndex(['additional_reference']);
            //$table->dropUnique(['additional_reference']);
        });
    }
}
