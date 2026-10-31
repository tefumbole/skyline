<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class WidenLetterTemplateColumns extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('letter_templates')) {
            return;
        }

        DB::statement('ALTER TABLE letter_templates MODIFY header MEDIUMTEXT NULL');
        DB::statement('ALTER TABLE letter_templates MODIFY body MEDIUMTEXT NULL');
        DB::statement('ALTER TABLE letter_templates MODIFY footer MEDIUMTEXT NULL');
        DB::statement('ALTER TABLE letter_templates MODIFY subject MEDIUMTEXT NOT NULL');
        DB::statement('ALTER TABLE letter_templates MODIFY created_by INT UNSIGNED NOT NULL');
    }

    public function down()
    {
        // Keep the wider columns. Shrinking them can cut saved templates.
    }
}
