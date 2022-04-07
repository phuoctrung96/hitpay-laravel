<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class BusinessInvoices extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('business_invoices', function (Blueprint $table) {
            $table->uuid('id');
            $table->uuid('business_id');
            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->uuid('user_id')->nullable();
            $table->uuid('business_customer_id')->nullable();
            $table->uuid('payment_request_id')->nullable();
            $table->string('reference', 255)->nullable();
            $table->string('email', 255)->nullable();
            $table->string('status', 32);
            $table->double('amount');
            $table->timestamps();

            $table->index($columns = [
                'business_id',
                'business_customer_id',
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
        Schema::dropIfExists('business_invoices');
    }
}
