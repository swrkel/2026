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
        // Add loading_sheet to the numbering_type enum
        DB::statement("ALTER TABLE distribution_prefix_settings MODIFY COLUMN numbering_type ENUM('sales_invoice', 'daily_summary_sheet', 'loading_sheet') NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove loading_sheet from the numbering_type enum (revert to original values)
        DB::statement("ALTER TABLE distribution_prefix_settings MODIFY COLUMN numbering_type ENUM('sales_invoice', 'daily_summary_sheet') NOT NULL");
    }
};
