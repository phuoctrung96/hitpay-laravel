<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTableBusinessSettings extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('business_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->string('key', 255);
            $table->tinyInteger('value')->default(0);
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index($columns = [
                'business_id',
                'id',
            ], _blueprint_hash_columns('index', $columns));

            $table->index($columns = [
                'business_id',
                'key',
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
        Schema::dropIfExists('business_settings');
    }
}
