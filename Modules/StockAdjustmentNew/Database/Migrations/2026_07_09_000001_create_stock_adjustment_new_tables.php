<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\StockAdjustmentNew\Services\StockAdjustmentSchemaService;

return new class extends Migration {
    public function up(): void
    {
        app(StockAdjustmentSchemaService::class)->ensure();
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS `san_v_stock_adjustment_summary`');
        Schema::dropIfExists('san_stock_adjustment_account_mappings');
        Schema::dropIfExists('san_stock_adjustment_settings');
        Schema::dropIfExists('san_stock_adjustment_audits');
        Schema::dropIfExists('san_stock_adjustment_movements');
        Schema::dropIfExists('san_stock_adjustment_lines');
        Schema::dropIfExists('san_stock_adjustments');
        Schema::dropIfExists('san_stock_adjustment_reasons');
    }
};
