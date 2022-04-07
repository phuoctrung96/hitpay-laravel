<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class BusinessUsersAddAcceptedAt extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('business_user', function (Blueprint $table) {
            $table->timestamp('invite_accepted_at')->nullable();
        });

        DB::table('business_user')
            ->update([
                'invite_accepted_at' => now()
            ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('business_user', function (Blueprint $table) {
            $table->dropColumn('invite_accepted_at');
        });
    }
}
