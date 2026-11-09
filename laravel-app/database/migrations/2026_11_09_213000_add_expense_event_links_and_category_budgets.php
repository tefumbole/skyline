<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddExpenseEventLinksAndCategoryBudgets extends Migration
{
    public function up()
    {
        if (Schema::hasTable('expense_categories') && ! Schema::hasColumn('expense_categories', 'budget_amount')) {
            Schema::table('expense_categories', function (Blueprint $table) {
                $table->decimal('budget_amount', 15, 2)->nullable();
            });
        }
        if (Schema::hasTable('expenses') && ! Schema::hasColumn('expenses', 'event_id')) {
            Schema::table('expenses', function (Blueprint $table) {
                $table->string('event_id', 64)->nullable();
                $table->unsignedInteger('quotation_id')->nullable();
                $table->index('event_id');
                $table->index('quotation_id');
            });
        }
        if (! Schema::hasTable('expense_payment_links')) {
            Schema::create('expense_payment_links', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('expense_id');
                $table->unsignedInteger('payment_id');
                $table->timestamps();
                $table->unique(['expense_id', 'payment_id']);
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('expense_payment_links');
        if (Schema::hasColumn('expenses', 'event_id')) {
            Schema::table('expenses', function (Blueprint $table) {
                $table->dropIndex(['event_id']);
                $table->dropIndex(['quotation_id']);
                $table->dropColumn(['event_id', 'quotation_id']);
            });
        }
        if (Schema::hasColumn('expense_categories', 'budget_amount')) {
            Schema::table('expense_categories', function (Blueprint $table) {
                $table->dropColumn('budget_amount');
            });
        }
    }
}
