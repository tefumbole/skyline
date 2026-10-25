<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UseBeyondMiniInvoice extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('general_settings') || ! Schema::hasColumn('general_settings', 'invoice_format')) {
            return;
        }
        DB::table('general_settings')->update(['invoice_format' => 'mini']);
    }

    public function down()
    {
    }
}
