<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBusinessFile extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('business_files', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id')->index();
            $table->string('group', 255);
            $table->string('media_type', 255);
            $table->string('disk', 255);
            $table->string('path', 255);
            $table->string('original_name', 255);
            $table->string('extension', 255);
            $table->unsignedInteger('storage_size');
            $table->text('remark')->nullable();
            $table->string('stripe_file_id', 255)->nullable();
            $table->json('data')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('business_associable_file', function (Blueprint $table) {
            $table->uuid('file_id')->index();
            $table->string('associable_type');
            $table->uuid('associable_id');

            $table->index($columns = [
                'associable_type',
                'associable_id',
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
        Schema::dropIfExists('business_file');
    }
}
