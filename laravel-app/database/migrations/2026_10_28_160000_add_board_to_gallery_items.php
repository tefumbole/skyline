<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBoardToGalleryItems extends Migration
{
    public function up()
    {
        Schema::table('gallery_items', function (Blueprint $table) {
            $table->string('board', 16)->default('gallery')->after('type');
            $table->index('board');
        });
    }

    public function down()
    {
        Schema::table('gallery_items', function (Blueprint $table) {
            $table->dropIndex(['board']);
            $table->dropColumn('board');
        });
    }
}
