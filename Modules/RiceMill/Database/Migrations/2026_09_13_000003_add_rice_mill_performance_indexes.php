<?php

use Illuminate\Database\Migrations\Migration;
use Modules\RiceMill\Services\PerformanceIndexService;

return new class extends Migration {
    public function up(): void
    {
        app(PerformanceIndexService::class)->apply();
    }

    public function down(): void
    {
        app(PerformanceIndexService::class)->rollback();
    }
};
