<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBusinessPaymentRequestTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('business_payment_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->unsignedBigInteger('amount');            
            $table->string('currency');
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->json('payment_methods')->nullable();            
            $table->string('purpose')->nullable();
            $table->string('reference_number')->nullable();
            $table->string('url')->nullable();
            $table->string('redirect_url')->nullable();
            $table->string('webhook')->nullable();            
            $table->boolean('send_sms')->default(true);
            $table->boolean('send_email')->default(true);
            $table->boolean('allow_repeated_payments')->default(true);
            $table->datetime('expiry_date')->nullable();

            $table->timestamps();
            $table->softDeletes();

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
        Schema::dropIfExists('business_payment_requests');
    }
}
