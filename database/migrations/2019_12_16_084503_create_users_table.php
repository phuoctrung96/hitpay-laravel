<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('display_name', 64)->nullable();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->date('birth_date')->nullable();
            $table->string('gender', 16)->nullable();
            $table->string('referral_code', 32)->nullable()->unique('unique_referral_code');
            $table->string('email')->nullable()->unique('unique_email');
            $table->string('phone_number', 32)->nullable()->unique('unique_phone_number');
            $table->string('password')->nullable();
            $table->string('authentication_secret')->nullable();
            $table->string('locale', 8);
            $table->boolean('email_login_enabled');
            $table->rememberToken();
            $table->timestamps();
            $table->timestamp('password_updated_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('phone_number_verified_at')->nullable();
            $table->timestamp('banned_at')->nullable();
            $table->softDeletes();
        });

        Schema::table('logs', function (Blueprint $table) {
            $table->uuid('executor_id')->nullable()->after('data');
            $table->uuid('related_user_id')->nullable()->after('associable_id');

            $table->index($columns = [
                'executor_id',
                'id',
            ], _blueprint_hash_columns('index', $columns));

            $table->dropIndex(_blueprint_hash_columns('index', [
                'associable_type',
                'associable_id',
            ]));

            $table->index($columns = [
                'associable_type',
                'associable_id',
                'related_user_id',
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
                'related_user_id',
            ]));

            $table->index($columns = [
                'associable_type',
                'associable_id',
            ], _blueprint_hash_columns('index', $columns));

            $table->dropIndex(_blueprint_hash_columns('index', [
                'executor_id',
                'id',
            ]));

            $table->dropColumn('related_user_id');
            $table->dropColumn('executor_id');
        });

        Schema::dropIfExists('users');
    }
}
