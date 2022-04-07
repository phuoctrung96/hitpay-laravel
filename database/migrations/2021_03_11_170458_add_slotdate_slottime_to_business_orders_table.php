<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSlotdateSlottimeToBusinessOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('business_orders', function (Blueprint $table) {
            $table->timestamp('slot_date')->after('shipping_tax_amount')->nullable();
            $table->string('slot_time', 1000)->after('slot_date')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('business_orders', function (Blueprint $table) {
            $table->dropColumn('slot_date');
            $table->dropColumn('slot_time');
        });
    }
}
