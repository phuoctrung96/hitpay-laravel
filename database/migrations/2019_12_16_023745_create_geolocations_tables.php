<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGeolocationsTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $common = function (Blueprint $table) {
            $table->char('country_code', 2)->nullable();
            $table->string('country_name', 64)->nullable();
            $table->string('region_name', 128)->nullable();
            $table->string('city_name', 128)->nullable();
            $table->double('latitude')->nullable();
            $table->double('longitude')->nullable();

            $table->index('ip_to', 'index_ip_to');
            $table->index($columns = [
                'ip_from',
                'ip_to',
            ], _blueprint_hash_columns('index', $columns));
        };

        foreach ([
            'ipv4_geolocations',
            'temp_ipv4_geolocations',
        ] as $tableName) {
            Schema::create($tableName, function (Blueprint $table) use ($common) {
                $table->unsignedInteger('ip_from');
                $table->unsignedInteger('ip_to');

                $common($table);
            });
        }

        foreach ([
            'ipv6_geolocations',
            'temp_ipv6_geolocations',
        ] as $tableName) {
            Schema::create($tableName, function (Blueprint $table) use ($common) {
                $table->decimal('ip_from', 39, 0);
                $table->decimal('ip_to', 39, 0);

                $common($table);
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('temp_ipv6_geolocations');
        Schema::dropIfExists('ipv6_geolocations');
        Schema::dropIfExists('temp_ipv4_geolocations');
        Schema::dropIfExists('ipv4_geolocations');
    }
}
