<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ModifyBusinessChargesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
      Schema::table('business_charges', function (Blueprint $table) {
        // add method rules
        $table->boolean('admin_fee')->default(false);
      });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
      Schema::table('business_charges', function (Blueprint $table) {
        $table->dropColumn('admin_fee');
      });
    }
}
