<?php

use Illuminate\Database\Migrations\Migration;
use Modules\StockAdjustmentNew\Services\StockAdjustmentSchemaService;

return new class extends Migration {
    public function up(): void
    {
        app(StockAdjustmentSchemaService::class)->ensure();
    }

    public function down(): void
    {
        // Posting audit columns are intentionally retained to preserve history.
    }
};
