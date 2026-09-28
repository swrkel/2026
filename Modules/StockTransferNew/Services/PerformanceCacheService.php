<?php
namespace Modules\StockTransferNew\Services;

use Illuminate\Support\Facades\Cache;
use Modules\StockTransferNew\Entities\Transfer;
use Modules\StockTransferNew\Entities\TransferMovement;

class PerformanceCacheService
{
    protected int $ttl = 300;

    public function key(string $name, ?int $businessId = null): string
    {
        return 'stn:perf:' . ($businessId ?: 'all') . ':' . $name;
    }

    public function dashboard(?int $businessId = null): array
    {
        return Cache::remember($this->key('dashboard', $businessId), $this->ttl, function () use ($businessId) {
            $transfer = Transfer::query();
            $movement = TransferMovement::query();
            if ($businessId) {
                $transfer->where('business_id', $businessId);
                $movement->where('business_id', $businessId);
            }
            return [
                'total_transfers' => (clone $transfer)->count(),
                'draft_transfers' => (clone $transfer)->where('status', 'draft')->count(),
                'pending_transfers' => (clone $transfer)->whereIn('status', ['submitted','pending_approval'])->count(),
                'in_transit_transfers' => (clone $transfer)->where('status', 'dispatched')->count(),
                'completed_transfers' => (clone $transfer)->where('status', 'received')->count(),
                'movement_rows' => (clone $movement)->count(),
            ];
        });
    }

    public function warm(?int $businessId = null): array
    {
        $this->forget($businessId);
        return $this->dashboard($businessId);
    }

    public function forget(?int $businessId = null): void
    {
        foreach (['dashboard'] as $name) {
            Cache::forget($this->key($name, $businessId));
        }
    }
}
