<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBusinessSubscribedEventsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('business_subscribed_events', function (Blueprint $table) {
            $table->uuid('business_id');
            $table->string('event', 64);
            $table->string('channel', 32);

            $table->unique($columns = [
                'business_id',
                'event',
                'channel',
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
        Schema::dropIfExists('business_subscribed_events');
    }
}
