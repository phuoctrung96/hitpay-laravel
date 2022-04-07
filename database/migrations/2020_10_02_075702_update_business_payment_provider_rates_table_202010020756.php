<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateBusinessPaymentProviderRatesTable202010020756 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('business_payment_provider_rates', function (Blueprint $table) {
            $table->string('scenario')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('business_payment_provider_rates', function (Blueprint $table) {
            $table->dropColumn('scenario');
        });
    }
}
