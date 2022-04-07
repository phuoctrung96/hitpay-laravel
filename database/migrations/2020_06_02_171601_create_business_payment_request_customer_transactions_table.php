<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBusinessPaymentRequestCustomerTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('business_payment_request_customer_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_payment_request_id');
            $table->uuid('business_customer_id');

            $table->timestamps();

            $table->index($columns = [
                'business_payment_request_id',
                'id',
            ], _blueprint_hash_columns('index', $columns));

            $table->index($columns = [
                'business_customer_id',
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
        Schema::dropIfExists('business_payment_request_customer_transactions');
    }
}
