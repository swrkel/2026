<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Modules\StockAdjustmentNew\Services\StockAdjustmentSchemaService;

return new class extends Migration {
    public function up(): void
    {
        app(StockAdjustmentSchemaService::class)->ensure();
    }

    public function down(): void
    {
        Schema::dropIfExists('san_stock_adjustment_account_mappings');
        Schema::dropIfExists('san_stock_adjustment_settings');
    }
};
