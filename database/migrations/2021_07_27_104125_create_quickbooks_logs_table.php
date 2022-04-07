<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateQuickbooksLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('quickbooks_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->uuid('business_charge_id');
            $table->boolean('is_fee');
            $table->string('quickbooks_invoice_id')->nullable();
            $table->text('payload')->nullable();
            $table->text('quickbooks_invoice')->nullable();
            $table->timestamps();

            $table->foreign('business_charge_id')
                ->references('id')
                ->on('business_charges')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('quickbooks_logs');
    }
}
