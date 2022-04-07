<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeColumnRedirectWebhook extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('business_payment_requests', function (Blueprint $table) {
            $table->text('webhook')->change();
            $table->text('redirect_url')->change();
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
            $table->string('webhook', 255)->change();
            $table->string('redirect_url', 255)->change();
        });
    }
}
