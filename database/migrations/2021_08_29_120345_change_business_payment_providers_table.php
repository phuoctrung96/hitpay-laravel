<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeBusinessPaymentProvidersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
      Schema::table('business_payment_providers', function (Blueprint $table) {
        $table->string('onboarding_status')->default('')->change();          
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
        // Surprisingly or not, for NOT NULL fields ->default(null) removes the default from a table
        $table->string('onboarding_status')->default(null)->change();          
      });        
    }
}
