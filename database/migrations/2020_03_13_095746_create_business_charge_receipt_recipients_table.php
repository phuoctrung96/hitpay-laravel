<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBusinessChargeReceiptRecipientsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('business_charge_receipt_recipients', function (Blueprint $table) {
            $table->uuid('business_charge_id')->index('index_charge_id');
            $table->string('email')->index('index_email');
            $table->timestamp('sent_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('business_charge_receipt_recipients');
    }
}
