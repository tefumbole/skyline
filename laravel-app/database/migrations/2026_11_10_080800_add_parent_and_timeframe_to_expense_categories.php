<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddParentAndTimeframeToExpenseCategories extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('expense_categories')) {
            return;
        }
        Schema::table('expense_categories', function (Blueprint $table) {
            if (! Schema::hasColumn('expense_categories', 'parent_id')) {
                $table->unsignedInteger('parent_id')->nullable()->index();
            }
            if (! Schema::hasColumn('expense_categories', 'budget_months')) {
                $table->unsignedTinyInteger('budget_months')->nullable();
            }
        });
    }

    public function down()
    {
        if (! Schema::hasTable('expense_categories')) {
            return;
        }
        Schema::table('expense_categories', function (Blueprint $table) {
            if (Schema::hasColumn('expense_categories', 'parent_id')) {
                $table->dropColumn('parent_id');
            }
            if (Schema::hasColumn('expense_categories', 'budget_months')) {
                $table->dropColumn('budget_months');
            }
        });
    }
}
