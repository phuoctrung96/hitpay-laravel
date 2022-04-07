<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFailedAuthenticationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('failed_authentications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->string('email')->nullable();
            $table->string('reason', 64);
            $table->ipAddress('request_ip_address');
            $table->string('request_user_agent', 1024)->nullable();
            $table->string('request_method', 8)->nullable();
            $table->string('request_url', 1024)->nullable();
            $table->char('request_country', 2)->nullable();
            $table->json('request_data')->nullable();
            $table->timestamp('logged_at')->nullable();

            $table->index($columns = [
                'user_id',
                'id',
            ], _blueprint_hash_columns('index', $columns));
        });

        Schema::table('user_logs', function (Blueprint $table) {
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
        Schema::table('user_logs', function (Blueprint $table) {
            $table->dropIndex(_blueprint_hash_columns('index', [
                'associable_type',
                'associable_id',
            ]));

            $table->dropColumn('associable_id');
            $table->dropColumn('associable_type');
        });

        Schema::dropIfExists('failed_authentications');
    }
}
