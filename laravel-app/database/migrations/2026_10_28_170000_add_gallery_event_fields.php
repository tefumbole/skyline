<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddGalleryEventFields extends Migration
{
    public function up()
    {
        Schema::table('gallery_items', function (Blueprint $table) {
            if (! Schema::hasColumn('gallery_items', 'event_id')) {
                $table->string('event_id', 64)->nullable()->after('description');
            }
            if (! Schema::hasColumn('gallery_items', 'event_label')) {
                $table->string('event_label', 120)->nullable()->after('event_id');
            }
            if (! Schema::hasColumn('gallery_items', 'thumb_path')) {
                $table->string('thumb_path')->nullable()->after('file_path');
            }
        });
    }

    public function down()
    {
        Schema::table('gallery_items', function (Blueprint $table) {
            $drop = [];
            foreach (['event_label', 'event_id', 'thumb_path'] as $column) {
                if (Schema::hasColumn('gallery_items', $column)) {
                    $drop[] = $column;
                }
            }
            if ($drop) {
                $table->dropColumn($drop);
            }
        });
    }
}
