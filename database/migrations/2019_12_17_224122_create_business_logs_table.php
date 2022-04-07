<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBusinessLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('business_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->string('group', 32);
            $table->string('event', 32);
            $table->string('business_associable_type')->nullable();
            $table->uuid('business_associable_id')->nullable();
            $table->string('relatable_type')->nullable();
            $table->uuid('relatable_id')->nullable();
            $table->json('data')->nullable();
            $table->uuid('executor_id')->nullable();
            $table->ipAddress('request_ip_address');
            $table->string('request_user_agent', 1024)->nullable();
            $table->string('request_method', 8)->nullable();
            $table->string('request_url', 1024)->nullable();
            $table->char('request_country', 2)->nullable();
            $table->json('request_data')->nullable();
            $table->timestamp('logged_at')->nullable();

            $table->index($columns = [
                'business_id',
                'id',
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
        Schema::dropIfExists('business_logs');
    }
}
