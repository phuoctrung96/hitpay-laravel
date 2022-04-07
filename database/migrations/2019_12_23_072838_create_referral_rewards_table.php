<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReferralRewardsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('referral_rewards', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id')->index('index_user_id');
            $table->char('country', 2);
            $table->string('event');
            $table->string('associable_type')->nullable();
            $table->uuid('associable_id')->nullable();
            $table->char('currency', 3);
            $table->unsignedBigInteger('amount');
            $table->timestamp('rewarded_at')->nullable();
            $table->timestamp('claimed_at')->nullable();

            $table->index($columns = [
                'user_id',
                'id',
            ], _blueprint_hash_columns('index', $columns));

            $table->index($columns = [
                'associable_type',
                'associable_id',
                'user_id',
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
        Schema::dropIfExists('referral_rewards');
    }
}
