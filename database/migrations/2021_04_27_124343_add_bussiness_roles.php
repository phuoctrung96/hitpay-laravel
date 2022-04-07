<?php

use App\Enumerations\BusinessRoleType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBussinessRoles extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        foreach (\App\Enumerations\BusinessRole::listConstants() as $role) {
            \App\Role::create([
                'type' => \App\Enumerations\RoleType::BUSINESS,
                'title' => $role
            ]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        \App\Role::whereIn('title', \App\Enumerations\BusinessRole::listConstants())->delete();
    }
}
