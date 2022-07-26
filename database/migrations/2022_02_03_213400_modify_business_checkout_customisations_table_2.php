<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ModifyBusinessCheckoutCustomisationsTable2 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
      Schema::table('business_checkout_customisations', function (Blueprint $table) {
        // add method rules
        $table->text('admin_fee_settings')->nullable();
      });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
      Schema::table('business_checkout_customisations', function (Blueprint $table) {
        $table->dropColumn('admin_fee_settings');
      });
    }
}
