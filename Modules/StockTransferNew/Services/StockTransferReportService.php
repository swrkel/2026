<?php

namespace Modules\StockTransferNew\Services;

use Illuminate\Support\Facades\DB;
use Modules\StockTransferNew\Entities\StockTransfer;
use Modules\StockTransferNew\Entities\StockTransferBalance;
use Modules\StockTransferNew\Entities\StockTransferMovement;
use Modules\StockTransferNew\Utilities\StockTransferTenant;

class StockTransferReportService
{

    /**
     * Return tenant/business-scoped dashboard totals.
     *
     * The dashboard controller previously called this method although it was
     * missing from the service, causing an HTTP 500 error.
     */
    public function summary(?int $businessId = null): array
    {
        $businessId = $businessId ?: (int) StockTransferTenant::businessId();

        $base = StockTransfer::query()
            ->where('business_id', $businessId);

        $countFor = static function ($query, array $statuses): int {
            return (int) (clone $query)
                ->whereIn('status', $statuses)
                ->count();
        };

        return [
            'total_transfers' => (int) (clone $base)->count(),
            'draft' => $countFor($base, ['draft']),
            'pending_approval' => $countFor($base, [
                'submitted',
                'pending',
                'pending_approval',
                'awaiting_approval',
            ]),
            'approved' => $countFor($base, ['approved']),
            'ready_for_dispatch' => $countFor($base, [
                'approved',
                'ready_for_dispatch',
            ]),
            'in_transit' => $countFor($base, [
                'dispatched',
                'in_transit',
                'partially_dispatched',
            ]),
            'partially_received' => $countFor($base, [
                'partial_receive',
                'partially_received',
            ]),
            'completed' => $countFor($base, [
                'received',
                'completed',
                'closed',
            ]),
            'rejected_returned' => $countFor($base, [
                'rejected',
                'returned',
                'returned_for_correction',
            ]),
            'cancelled' => $countFor($base, ['cancelled']),
        ];
    }

    public function register(array $filters = [])
    {
        $query = StockTransfer::withCount('lines')->where('business_id', StockTransferTenant::businessId())->latest();
        $this->applyDateStatusFilters($query, $filters);
        return $query->paginate(50);
    }

    public function movement(array $filters = [])
    {
        $query = StockTransferMovement::where('business_id', StockTransferTenant::businessId())->latest('movement_date');
        if (!empty($filters['product_id'])) $query->where('product_id', $filters['product_id']);
        if (!empty($filters['from_date'])) $query->whereDate('movement_date', '>=', $filters['from_date']);
        if (!empty($filters['to_date'])) $query->whereDate('movement_date', '<=', $filters['to_date']);
        return $query->paginate(100);
    }

    public function balances(array $filters = [])
    {
        $query = StockTransferBalance::where('business_id', StockTransferTenant::businessId());
        if (!empty($filters['product_id'])) $query->where('product_id', $filters['product_id']);
        if (!empty($filters['location_id'])) $query->where('business_location_id', $filters['location_id']);
        if (!empty($filters['store_id'])) $query->where('store_id', $filters['store_id']);
        return $query->orderByDesc('last_movement_at')->paginate(100);
    }

    public function inTransit(array $filters = [])
    {
        $query = StockTransfer::with('lines')->where('business_id', StockTransferTenant::businessId())->where('status', 'in_transit')->latest('dispatched_at');
        $this->applyDateStatusFilters($query, $filters);
        return $query->paginate(50);
    }

    public function variance(array $filters = [])
    {
        $query = DB::table('stnew_stock_transfer_lines as l')
            ->join('stnew_stock_transfers as t', 't.id', '=', 'l.transfer_id')
            ->where('t.business_id', StockTransferTenant::businessId())
            ->where(function ($q) {$q->where('l.short_qty', '>', 0)->orWhere('l.excess_qty', '>', 0);})
            ->select('t.transfer_no','t.transfer_date','t.status','l.product_id','l.variation_id','l.qty_requested','l.qty_dispatched','l.qty_received','l.short_qty','l.excess_qty','l.remarks')
            ->orderByDesc('t.transfer_date');
        if (!empty($filters['from_date'])) $query->whereDate('t.transfer_date', '>=', $filters['from_date']);
        if (!empty($filters['to_date'])) $query->whereDate('t.transfer_date', '<=', $filters['to_date']);
        return $query->paginate(100);
    }

    public function aging(array $filters = [])
    {
        $query = StockTransfer::where('business_id', StockTransferTenant::businessId())->whereIn('status', ['pending','approved','in_transit'])->orderBy('transfer_date');
        $this->applyDateStatusFilters($query, $filters);
        return $query->paginate(100);
    }

    public function exportRows(string $type, array $filters = []): array
    {
        if ($type === 'variance') return $this->variance($filters)->getCollection()->map(fn ($r) => (array) $r)->all();
        if ($type === 'aging') return $this->aging($filters)->getCollection()->map(fn ($r) => [$r->transfer_no, optional($r->transfer_date)->format('Y-m-d'), $r->status, $r->from_location_id, $r->to_location_id, $r->created_at ? $r->created_at->diffInDays(now()) : 0])->all();
        return [];
    }

    protected function applyDateStatusFilters($query, array $filters): void
    {
        if (!empty($filters['status'])) $query->where('status', $filters['status']);
        if (!empty($filters['from_date'])) $query->whereDate('transfer_date', '>=', $filters['from_date']);
        if (!empty($filters['to_date'])) $query->whereDate('transfer_date', '<=', $filters['to_date']);
    }
}
