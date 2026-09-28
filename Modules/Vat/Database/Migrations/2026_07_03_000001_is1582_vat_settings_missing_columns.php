<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Is1582VatSettingsMissingColumns extends Migration
{
    public function up()
    {
        Schema::table('vat_statement_logos', function (Blueprint $table) {
            if (!Schema::hasColumn('vat_statement_logos', 'statement_date')) {
                $table->date('statement_date')->nullable()->after('image_name');
            }
        });

        Schema::table('vat_expense_categories', function (Blueprint $table) {
            if (!Schema::hasColumn('vat_expense_categories', 'payee_id')) {
                $table->unsignedInteger('payee_id')->nullable()->default(0)->after('expense_account_id');
            }
        });

        Schema::table('vat_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('vat_settings', 'location_id')) {
                $table->unsignedInteger('location_id')->nullable()->after('tax_report_name');
            }
            if (!Schema::hasColumn('vat_settings', 'user_id')) {
                $table->unsignedInteger('user_id')->nullable()->after('location_id');
            }
            if (!Schema::hasColumn('vat_settings', 'prefix_id')) {
                $table->unsignedInteger('prefix_id')->nullable()->after('user_id');
            }
            if (!Schema::hasColumn('vat_settings', 'prefix_id2')) {
                $table->unsignedInteger('prefix_id2')->nullable()->after('prefix_id');
            }
        });
    }

    public function down()
    {
        Schema::table('vat_settings', function (Blueprint $table) {
            foreach (['prefix_id2', 'prefix_id', 'user_id', 'location_id'] as $column) {
                if (Schema::hasColumn('vat_settings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('vat_expense_categories', function (Blueprint $table) {
            if (Schema::hasColumn('vat_expense_categories', 'payee_id')) {
                $table->dropColumn('payee_id');
            }
        });

        Schema::table('vat_statement_logos', function (Blueprint $table) {
            if (Schema::hasColumn('vat_statement_logos', 'statement_date')) {
                $table->dropColumn('statement_date');
            }
        });
    }
}
