<?php

namespace Modules\StockTransferNew\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\StockTransferNew\Entities\StockTransfer;
use Modules\StockTransferNew\Utilities\StockTransferTenant;

class StockTransferCommandCenterService
{
    public function dashboard(?int $businessId = null): array
    {
        $businessId = $businessId ?: (int) StockTransferTenant::businessId();
        $base = StockTransfer::where('business_id', $businessId);
        $today = Carbon::today();

        return [
            'kpis' => [
                'draft' => (clone $base)->where('status', 'draft')->count(),
                'pending_approval' => (clone $base)->where('status', 'pending')->count(),
                'approved_waiting_dispatch' => (clone $base)->where('status', 'approved')->count(),
                'in_transit' => (clone $base)->where('status', 'in_transit')->count(),
                'received_today' => (clone $base)->where('status', 'received')->whereDate('received_at', $today)->count(),
                'delayed' => $this->delayedQuery($businessId)->count(),
                'with_variance' => $this->varianceQuery($businessId)->count(),
                'cancelled_this_month' => (clone $base)->where('status', 'cancelled')->whereMonth('cancelled_at', $today->month)->whereYear('cancelled_at', $today->year)->count(),
            ],
            'pending_approval' => (clone $base)->withCount('lines')->where('status', 'pending')->latest('submitted_at')->limit(10)->get(),
            'approved_waiting_dispatch' => (clone $base)->withCount('lines')->where('status', 'approved')->latest('approved_at')->limit(10)->get(),
            'in_transit' => (clone $base)->withCount('lines')->where('status', 'in_transit')->latest('dispatched_at')->limit(10)->get(),
            'delayed' => $this->delayedQuery($businessId)->withCount('lines')->limit(10)->get(),
            'variance' => $this->varianceQuery($businessId)->withCount('lines')->latest('received_at')->limit(10)->get(),
            'recent_activity' => DB::table('stnew_stock_transfer_audits as a')
                ->join('stnew_stock_transfers as t', 't.id', '=', 'a.transfer_id')
                ->where('t.business_id', $businessId)
                ->select('a.*', 't.transfer_no', 't.status')
                ->orderByDesc('a.created_at')
                ->limit(15)
                ->get(),
            'status_chart' => (clone $base)
                ->select('status', DB::raw('COUNT(*) as total'))
                ->groupBy('status')
                ->pluck('total', 'status')
                ->toArray(),
        ];
    }

    public function operationalQueue(array $filters = [])
    {
        $query = StockTransfer::withCount('lines')->where('business_id', StockTransferTenant::businessId());
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        } else {
            $query->whereIn('status', ['draft', 'pending', 'approved', 'in_transit']);
        }
        if (!empty($filters['from_location_id'])) $query->where('from_location_id', $filters['from_location_id']);
        if (!empty($filters['to_location_id'])) $query->where('to_location_id', $filters['to_location_id']);
        if (!empty($filters['from_date'])) $query->whereDate('transfer_date', '>=', $filters['from_date']);
        if (!empty($filters['to_date'])) $query->whereDate('transfer_date', '<=', $filters['to_date']);
        return $query->orderByRaw("FIELD(status,'in_transit','pending','approved','draft')")
            ->orderByDesc('updated_at')
            ->paginate(50);
    }

    protected function delayedQuery(int $businessId)
    {
        return StockTransfer::where('business_id', $businessId)
            ->whereIn('status', ['pending', 'approved', 'in_transit'])
            ->whereDate('transfer_date', '<', Carbon::today());
    }

    protected function varianceQuery(int $businessId)
    {
        return StockTransfer::where('business_id', $businessId)
            ->whereExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('stnew_stock_transfer_lines as l')
                    ->whereColumn('l.transfer_id', 'stnew_stock_transfers.id')
                    ->where(function ($x) {
                        $x->where('l.short_qty', '>', 0)->orWhere('l.excess_qty', '>', 0);
                    });
            });
    }
}
