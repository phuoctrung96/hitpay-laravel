<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class BusinessRolesRestrictionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('business_roles_restrictions', function (Blueprint $table) {
            $table->uuid('business_id');
            $table->string('role', 64);
            $table->string('restriction', 32);

            $table->unique($columns = [
                'business_id',
                'role',
                'restriction',
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
        Schema::dropIfExists('business_roles_restrictions');
    }
}
