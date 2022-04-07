<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldsToBusinessPaymentRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('business_payment_requests', function (Blueprint $table) {
            $table->string('phone')->nullable();
            $table->string('status')->default('pending');
            $table->string('sms_status')->default('pending');
            $table->string('email_status')->default('pending');
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
            $table->dropColumn('phone');
            $table->dropColumn('status');
            $table->dropColumn('sms_status');
            $table->dropColumn('email_status');
        });
    }
}
