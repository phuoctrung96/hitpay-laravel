<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class BusinessPaymentRequestsAddChannelColumn extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('business_payment_requests', function (Blueprint $table) {
            $table->string('channel')->default(\App\Business\PaymentRequest::DEFAULT_CHANNEL)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('business_payment_requests', function (Blueprint $table) {
            $table->dropColumn('channel');
        });
    }
}
