<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSubscribedFeaturesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('subscribed_features', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->string('feature', 32);
            $table->char('currency', 3);
            $table->unsignedBigInteger('price');
            $table->text('remark')->nullable();
            $table->text('appendix')->nullable();
            $table->string('renewal_cycle', 32);
            $table->boolean('active');
            $table->boolean('auto_renew');
            $table->timestamps();
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('expires_at')->nullable();

            $table->index($columns = [
                'business_id',
                'id',
            ], _blueprint_hash_columns('index', $columns));

            $table->index($columns = [
                'business_id',
                'feature',
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
        Schema::dropIfExists('subscribed_features');
    }
}
