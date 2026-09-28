<?php

namespace Modules\RestaurantNew\Services;

use Illuminate\Support\Facades\DB;
use Modules\RestaurantNew\Entities\RestaurantNewBranchTransfer;
use Modules\RestaurantNew\Entities\RestaurantNewBranchTransferLine;

class MultiBranchOperationsService
{
    public function createTransfer(array $header, array $lines): RestaurantNewBranchTransfer
    {
        return DB::transaction(function () use ($header, $lines) {
            $transfer = RestaurantNewBranchTransfer::create(array_merge($header, [
                'status' => $header['status'] ?? 'draft',
                'requested_at' => now(),
            ]));

            foreach ($lines as $line) {
                RestaurantNewBranchTransferLine::create(array_merge($line, [
                    'restaurant_new_branch_transfer_id' => $transfer->id,
                    'line_total' => ($line['approved_qty'] ?? $line['requested_qty'] ?? 0) * ($line['unit_cost'] ?? 0),
                ]));
            }

            return $transfer;
        });
    }

    public function approveTransfer(RestaurantNewBranchTransfer $transfer, int $userId): RestaurantNewBranchTransfer
    {
        $transfer->update([
            'status' => 'approved',
            'approved_by' => $userId,
            'approved_at' => now(),
        ]);
        return $transfer->refresh();
    }

    public function markDispatched(RestaurantNewBranchTransfer $transfer, int $userId): RestaurantNewBranchTransfer
    {
        $transfer->update([
            'status' => 'dispatched',
            'dispatched_by' => $userId,
            'dispatched_at' => now(),
        ]);
        return $transfer->refresh();
    }

    public function markReceived(RestaurantNewBranchTransfer $transfer, int $userId): RestaurantNewBranchTransfer
    {
        $transfer->update([
            'status' => 'received',
            'received_by' => $userId,
            'received_at' => now(),
        ]);
        return $transfer->refresh();
    }

    public function comparisonSummary(array $filters = []): array
    {
        $query = DB::table('restaurant_new_branch_comparison_snapshots')
            ->select('business_location_id', DB::raw('SUM(sales_total) as sales_total'), DB::raw('SUM(food_cost_total) as food_cost_total'), DB::raw('SUM(gross_profit_total) as gross_profit_total'), DB::raw('SUM(wastage_total) as wastage_total'))
            ->groupBy('business_location_id');

        if (!empty($filters['business_id'])) {
            $query->where('business_id', $filters['business_id']);
        }
        if (!empty($filters['date_from'])) {
            $query->whereDate('snapshot_date', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->whereDate('snapshot_date', '<=', $filters['date_to']);
        }

        return $query->get()->toArray();
    }
}
