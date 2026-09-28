<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE distribution_prefix_settings MODIFY COLUMN numbering_type ENUM('sales_invoice', 'sales_order', 'daily_summary_sheet', 'loading_sheet') NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE distribution_prefix_settings MODIFY COLUMN numbering_type ENUM('sales_invoice', 'daily_summary_sheet', 'loading_sheet') NOT NULL");
    }
};
