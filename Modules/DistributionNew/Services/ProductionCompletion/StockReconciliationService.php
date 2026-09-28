<?php

namespace Modules\DistributionNew\Services\ProductionCompletion;

use Illuminate\Support\Facades\DB;

class StockReconciliationService
{
    public function createRun(int $businessId, ?int $locationId, string $date, ?int $userId): int
    {
        return DB::table('disnew_stock_reconciliation_runs')->insertGetId([
            'business_id' => $businessId,
            'location_id' => $locationId,
            'reconciliation_date' => $date,
            'status' => 'draft',
            'created_by' => $userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function addLine(int $runId, int $productId, ?int $warehouseId, ?int $vehicleId, float $systemQty, float $physicalQty, ?string $remarks = null): void
    {
        DB::table('disnew_stock_reconciliation_lines')->insert([
            'reconciliation_run_id' => $runId,
            'product_id' => $productId,
            'warehouse_id' => $warehouseId,
            'vehicle_id' => $vehicleId,
            'system_qty' => $systemQty,
            'physical_qty' => $physicalQty,
            'variance_qty' => $physicalQty - $systemQty,
            'remarks' => $remarks,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
