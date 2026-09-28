<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAccountAndVatInputToVatExpenseCategories extends Migration
{
    public function up()
    {
        Schema::table('vat_expense_categories', function (Blueprint $table) {
            if (!Schema::hasColumn('vat_expense_categories', 'expense_account_id')) {
                $table->unsignedInteger('expense_account_id')->nullable()->after('expense_code');
            }
            if (!Schema::hasColumn('vat_expense_categories', 'vat_input_claimed')) {
                $table->boolean('vat_input_claimed')->default(0)->after('expense_account_id');
            }
        });
    }

    public function down()
    {
        Schema::table('vat_expense_categories', function (Blueprint $table) {
            if (Schema::hasColumn('vat_expense_categories', 'vat_input_claimed')) {
                $table->dropColumn('vat_input_claimed');
            }
            if (Schema::hasColumn('vat_expense_categories', 'expense_account_id')) {
                $table->dropColumn('expense_account_id');
            }
        });
    }
}
