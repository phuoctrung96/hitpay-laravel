<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateBusinessTransfersTable202112171228 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('business_transfers', function (Blueprint $table) {
            $table->index($columns = [
                'payment_provider',
                'status',
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
        Schema::table('business_transfers', function (Blueprint $table) {
            $table->dropIndex(_blueprint_hash_columns('index', [
                'payment_provider',
                'status',
                'id',
            ]));
        });
    }
}
