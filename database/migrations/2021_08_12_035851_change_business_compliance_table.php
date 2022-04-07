<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeBusinessComplianceTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('business_compliance', function ($table) {
            $table->renameColumn('business_id', 'entity_id');
            $table->string('type')->after('business_id');
            $table->json('data')->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('business_compliance', function (Blueprint $table) {
            $table->renameColumn('entity_id', 'business_id');
            $table->dropColumn('type');
        });
    }
}
