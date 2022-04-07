<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBusinessPersonAssociables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('business_associable_persons', function (Blueprint $table) {
            $table->uuid('person_id')->index();
            $table->string('associable_type');
            $table->uuid('associable_id');
            $table->timestamps();

            $table->index($columns = [
                'associable_id',
                'associable_type',
            ], _blueprint_hash_columns('index', $columns));
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('business_associable_persons');
    }
}
