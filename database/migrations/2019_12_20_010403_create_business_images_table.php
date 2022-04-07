<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBusinessImagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('business_images', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->string('business_associable_type')->nullable();
            $table->uuid('business_associable_id')->nullable();
            $table->string('group', 64);
            $table->string('media_type');
            $table->string('disk');
            $table->string('path');
            $table->string('extension');
            $table->unsignedSmallInteger('height');
            $table->unsignedSmallInteger('width');
            $table->unsignedInteger('file_size');
            $table->unsignedInteger('storage_size');
            $table->text('caption')->nullable();
            $table->json('other_dimensions')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index($columns = [
                'business_id',
                'id',
            ], _blueprint_hash_columns('index', $columns));

            $table->index($columns = [
                'business_associable_type',
                'business_associable_id',
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
        Schema::dropIfExists('business_images');
    }
}
