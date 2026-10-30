<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddMissingOrderMenuColumns extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('orders')) {
            return;
        }

        $adds = [
            'is_donation' => 'TINYINT NOT NULL DEFAULT 0',
            'is_service' => 'TINYINT NOT NULL DEFAULT 0',
            'vendor_id' => 'INT NULL',
            'is_approve' => 'TINYINT NOT NULL DEFAULT 0',
            'order_received' => 'TINYINT NOT NULL DEFAULT 0',
            'payment_request' => 'INT NOT NULL DEFAULT 0',
            'delivery_date' => 'DATE NULL',
            'reference' => 'VARCHAR(191) NULL',
            'customer_doc' => 'VARCHAR(191) NULL',
            'result_doc' => 'VARCHAR(191) NULL',
            'sample_doc' => 'VARCHAR(191) NULL',
            'service_type' => 'VARCHAR(191) NULL',
            'academic_year' => 'VARCHAR(191) NULL',
            'variant_id' => 'INT NULL',
            'number_of_pages' => 'INT NULL',
            'word_count' => 'INT NULL',
            'spacing' => 'VARCHAR(191) NULL',
            'subject' => 'VARCHAR(191) NULL',
            'project_title' => 'VARCHAR(191) NULL',
            'project_guide_lines' => 'TEXT NULL',
            'citation_sytle' => 'VARCHAR(191) NULL',
            'citation_style' => 'VARCHAR(191) NULL',
            'font_style' => 'VARCHAR(191) NULL',
            'language' => 'VARCHAR(191) NULL',
            'references' => 'INT NULL',
            'quality_double_checker' => 'TINYINT NOT NULL DEFAULT 0',
            'abstract_page' => 'TINYINT NOT NULL DEFAULT 0',
            'one_page_summary' => 'TINYINT NOT NULL DEFAULT 0',
            'grammar_checker' => 'TINYINT NOT NULL DEFAULT 0',
            'preferred_expert' => 'TINYINT NOT NULL DEFAULT 0',
        ];

        foreach ($adds as $column => $ddl) {
            if (! Schema::hasColumn('orders', $column)) {
                DB::statement('ALTER TABLE `orders` ADD `'.$column.'` '.$ddl);
            }
        }
    }

    public function down()
    {
    }
}
