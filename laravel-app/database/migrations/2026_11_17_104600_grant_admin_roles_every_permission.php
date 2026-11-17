<?php

use Illuminate\Database\Migrations\Migration;

class GrantAdminRolesEveryPermission extends Migration
{
    public function up()
    {
        \App\Support\AdminPermissions::sync();
    }

    public function down()
    {
    }
}
