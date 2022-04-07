<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBusinessCheckoutCustomisationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('business_checkout_customisations', function (Blueprint $table) {
            $table->uuid('id');
            $table->uuid('business_id');
            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');            
            $table->string('theme', 16)->default('hitpay');
            $table->text('tint_color')->nullable();
            $table->longText('payment_order')->nullable();
            $table->longText('form_data')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('business_checkout_customisations');
    }
}
