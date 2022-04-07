<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEmailColumnToShopifyPayments extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('business_shopify_payments', function (Blueprint $table) {
            $table->string('email', 200);
            $table->index(['business_id', 'email', 'invoice_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('business_shopify_payments', function (Blueprint $table) {
            //
        });
    }
}
