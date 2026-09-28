<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('distribution_daily_summaries', function (Blueprint $table) {
            $table->string('loading_sheet_no')->nullable()->after('productive_calls_bf');
            $table->text('loading_sheets')->nullable()->after('loading_sheet_no');
            $table->text('stock_status')->nullable()->after('loading_sheets');
        });

        Schema::table('distribution_invoices', function (Blueprint $table) {
            $table->string('loading_sheet_no')->nullable()->after('invoice_no');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('distribution_daily_summaries', function (Blueprint $table) {
            $table->dropColumn(['loading_sheet_no', 'loading_sheets', 'stock_status']);
        });

        Schema::table('distribution_invoices', function (Blueprint $table) {
            $table->dropColumn('loading_sheet_no');
        });
    }
};
