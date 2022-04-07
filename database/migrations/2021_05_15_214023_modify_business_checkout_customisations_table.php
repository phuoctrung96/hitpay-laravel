<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ModifyBusinessCheckoutCustomisationsTable extends Migration
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
        $table->text('method_rules')->nullable();
        // fix no index issue
        $table->unique('id');
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
        $table->dropColumn('method_rules');
      });
    }
}
