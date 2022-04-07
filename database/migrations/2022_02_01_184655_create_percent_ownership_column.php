<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePercentOwnershipColumn extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('business_persons', function (Blueprint $table) {
            $table->decimal('percent_ownership')->nullable()->after('relationship');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('business_persons', function (Blueprint $table) {
            $table->dropColumn('percent_ownership');
        });
    }
}
