<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateBusinessPaymentProvidersTable20210902 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
      Schema::table('business_payment_providers', function (Blueprint $table) {
        $table->boolean('reported')->default(false);          
      });        
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
      Schema::table('business_payment_providers', function (Blueprint $table) {
        $table->dropColumn('reported');
      });
    }
}
