<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateConfigurationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('configurations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('configuration_key', 64)->unique('unique_configuration_key');
            $table->string('type', 16);
            $table->text('value')->nullable();
            $table->boolean('autoload');
            $table->timestamps();
        });

        Schema::table('logs', function (Blueprint $table) {
            $table->string('associable_type')->nullable()->after('event');
            $table->uuid('associable_id')->nullable()->after('associable_type');

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
        Schema::table('logs', function (Blueprint $table) {
            $table->dropIndex(_blueprint_hash_columns('index', [
                'associable_type',
                'associable_id',
            ]));

            $table->dropColumn('associable_id');
            $table->dropColumn('associable_type');
        });

        Schema::dropIfExists('configurations');
    }
}
