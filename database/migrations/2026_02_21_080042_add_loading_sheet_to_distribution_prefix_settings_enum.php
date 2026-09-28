<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        DB::statement("ALTER TABLE distribution_prefix_settings MODIFY COLUMN numbering_type ENUM('sales_invoice', 'daily_summary_sheet', 'stock_transfer', 'loading_sheet') NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        DB::statement("ALTER TABLE distribution_prefix_settings MODIFY COLUMN numbering_type ENUM('sales_invoice', 'daily_summary_sheet', 'stock_transfer') NOT NULL");
    }
};
