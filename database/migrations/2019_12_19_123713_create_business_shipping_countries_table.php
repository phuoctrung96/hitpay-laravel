<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBusinessShippingCountriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('business_shipping_countries', function (Blueprint $table) {
            $table->uuid('business_shipping_id');
            $table->char('country', 2);

            $table->unique($columns = [
                'business_shipping_id',
                'country',
            ], _blueprint_hash_columns('unique', $columns));
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('business_shipping_countries');
    }
}
