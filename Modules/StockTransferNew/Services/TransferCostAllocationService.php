<?php

namespace Modules\StockTransferNew\Services;

use Illuminate\Support\Facades\DB;

class TransferCostAllocationService
{
    public function calculateTotal(array $data): float
    {
        return round(
            (float)($data['vehicle_cost'] ?? 0) +
            (float)($data['fuel_cost'] ?? 0) +
            (float)($data['labour_cost'] ?? 0) +
            (float)($data['loading_cost'] ?? 0) +
            (float)($data['unloading_cost'] ?? 0) +
            (float)($data['misc_cost'] ?? 0),
            4
        );
    }

    public function allocationSummary(int $businessId): array
    {
        $row = DB::table('stn_transfer_cost_allocations')
            ->where('business_id', $businessId)
            ->selectRaw('COUNT(*) as count, COALESCE(SUM(total_cost),0) as total_cost')
            ->first();

        return [
            'records' => (int)($row->count ?? 0),
            'total_cost' => (float)($row->total_cost ?? 0),
            'pending_landed_cost' => DB::table('stn_transfer_cost_allocations')
                ->where('business_id', $businessId)
                ->where('landed_cost_applied', 0)
                ->count(),
        ];
    }
}
