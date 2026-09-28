<?php

namespace Modules\StockTransferNew\Services;

use Illuminate\Support\Facades\DB;

class TransferPolicyService
{
    public function matchingPolicy(int $businessId, array $transfer): ?object
    {
        return DB::table('stn_transfer_policies')
            ->where('business_id', $businessId)
            ->where('status', 'active')
            ->where(function ($query) use ($transfer) {
                $query->whereNull('from_location_id')->orWhere('from_location_id', $transfer['from_location_id'] ?? null);
            })
            ->where(function ($query) use ($transfer) {
                $query->whereNull('to_location_id')->orWhere('to_location_id', $transfer['to_location_id'] ?? null);
            })
            ->where(function ($query) use ($transfer) {
                $query->whereNull('from_store_id')->orWhere('from_store_id', $transfer['from_store_id'] ?? null);
            })
            ->where(function ($query) use ($transfer) {
                $query->whereNull('to_store_id')->orWhere('to_store_id', $transfer['to_store_id'] ?? null);
            })
            ->orderByRaw('from_store_id IS NULL, to_store_id IS NULL, from_location_id IS NULL, to_location_id IS NULL')
            ->first();
    }

    public function policySummary(int $businessId): array
    {
        return [
            'active' => DB::table('stn_transfer_policies')->where('business_id', $businessId)->where('status', 'active')->count(),
            'inactive' => DB::table('stn_transfer_policies')->where('business_id', $businessId)->where('status', 'inactive')->count(),
            'cost_required' => DB::table('stn_transfer_policies')->where('business_id', $businessId)->where('requires_cost_allocation', 1)->count(),
            'cancel_approval_required' => DB::table('stn_transfer_policies')->where('business_id', $businessId)->where('requires_cancellation_approval', 1)->count(),
        ];
    }
}
