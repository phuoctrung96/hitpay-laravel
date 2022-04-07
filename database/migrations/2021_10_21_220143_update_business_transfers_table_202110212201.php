<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateBusinessTransfersTable202110212201 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('business_transfers', function (Blueprint $table) {
            $table->timestamp('transferred_at')->nullable()->after('updated_at')->index();
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
            $table->dropColumn('transferred_at');
        });
    }
}
