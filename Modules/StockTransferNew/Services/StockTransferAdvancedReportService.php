<?php

namespace Modules\StockTransferNew\Services;

use Illuminate\Support\Facades\DB;
use Modules\StockTransferNew\Utilities\StockTransferTenant;

class StockTransferAdvancedReportService
{
    protected function businessId(): int
    {
        return StockTransferTenant::businessId();
    }

    protected function baseTransferQuery(array $filters = [])
    {
        $query = DB::table('stnew_stock_transfers as t')
            ->where('t.business_id', $this->businessId());

        if (!empty($filters['from_date'])) {
            $query->whereDate('t.transfer_date', '>=', $filters['from_date']);
        }
        if (!empty($filters['to_date'])) {
            $query->whereDate('t.transfer_date', '<=', $filters['to_date']);
        }
        if (!empty($filters['status'])) {
            $query->where('t.status', $filters['status']);
        }
        if (!empty($filters['from_location_id'])) {
            $query->where('t.from_location_id', $filters['from_location_id']);
        }
        if (!empty($filters['to_location_id'])) {
            $query->where('t.to_location_id', $filters['to_location_id']);
        }
        if (!empty($filters['from_store_id'])) {
            $query->where('t.from_store_id', $filters['from_store_id']);
        }
        if (!empty($filters['to_store_id'])) {
            $query->where('t.to_store_id', $filters['to_store_id']);
        }

        return $query;
    }

    public function productWise(array $filters = [])
    {
        $query = $this->baseTransferQuery($filters)
            ->join('stnew_stock_transfer_lines as l', 'l.transfer_id', '=', 't.id')
            ->select(
                'l.product_id',
                'l.variation_id',
                DB::raw('COUNT(DISTINCT t.id) as transfer_count'),
                DB::raw('SUM(l.qty_requested) as requested_qty'),
                DB::raw('SUM(l.qty_dispatched) as dispatched_qty'),
                DB::raw('SUM(l.qty_received) as received_qty'),
                DB::raw('SUM(l.short_qty) as short_qty'),
                DB::raw('SUM(l.excess_qty) as excess_qty')
            )
            ->groupBy('l.product_id', 'l.variation_id')
            ->orderByDesc('transfer_count');

        if (!empty($filters['product_id'])) {
            $query->where('l.product_id', $filters['product_id']);
        }

        return $query->paginate(100);
    }

    public function locationWise(array $filters = [])
    {
        return $this->baseTransferQuery($filters)
            ->select(
                't.from_location_id',
                't.to_location_id',
                DB::raw('COUNT(*) as transfer_count'),
                DB::raw('SUM(t.total_requested_qty) as requested_qty'),
                DB::raw('SUM(t.total_dispatched_qty) as dispatched_qty'),
                DB::raw('SUM(t.total_received_qty) as received_qty'),
                DB::raw('SUM(t.total_short_qty) as short_qty'),
                DB::raw('SUM(t.total_excess_qty) as excess_qty')
            )
            ->groupBy('t.from_location_id', 't.to_location_id')
            ->orderByDesc('transfer_count')
            ->paginate(100);
    }

    public function storeWise(array $filters = [])
    {
        return $this->baseTransferQuery($filters)
            ->select(
                't.from_store_id',
                't.to_store_id',
                DB::raw('COUNT(*) as transfer_count'),
                DB::raw('SUM(t.total_requested_qty) as requested_qty'),
                DB::raw('SUM(t.total_dispatched_qty) as dispatched_qty'),
                DB::raw('SUM(t.total_received_qty) as received_qty'),
                DB::raw('SUM(t.total_short_qty) as short_qty'),
                DB::raw('SUM(t.total_excess_qty) as excess_qty')
            )
            ->groupBy('t.from_store_id', 't.to_store_id')
            ->orderByDesc('transfer_count')
            ->paginate(100);
    }

    public function userWise(array $filters = [])
    {
        return $this->baseTransferQuery($filters)
            ->select(
                't.created_by', 't.approved_by', 't.dispatched_by', 't.received_by',
                DB::raw('COUNT(*) as transfer_count'),
                DB::raw('SUM(t.total_requested_qty) as requested_qty'),
                DB::raw('SUM(t.total_dispatched_qty) as dispatched_qty'),
                DB::raw('SUM(t.total_received_qty) as received_qty')
            )
            ->groupBy('t.created_by', 't.approved_by', 't.dispatched_by', 't.received_by')
            ->orderByDesc('transfer_count')
            ->paginate(100);
    }

    public function vehicleWise(array $filters = [])
    {
        return $this->baseTransferQuery($filters)
            ->select(
                't.vehicle_no', 't.driver_name',
                DB::raw('COUNT(*) as transfer_count'),
                DB::raw('SUM(t.total_dispatched_qty) as dispatched_qty'),
                DB::raw('SUM(t.total_received_qty) as received_qty'),
                DB::raw('SUM(t.total_short_qty) as short_qty'),
                DB::raw('SUM(t.total_excess_qty) as excess_qty')
            )
            ->where(function ($q) {
                $q->whereNotNull('t.vehicle_no')->orWhereNotNull('t.driver_name');
            })
            ->groupBy('t.vehicle_no', 't.driver_name')
            ->orderByDesc('transfer_count')
            ->paginate(100);
    }

    public function monthlyTrend(array $filters = [])
    {
        return $this->baseTransferQuery($filters)
            ->select(
                DB::raw("DATE_FORMAT(t.transfer_date, '%Y-%m') as transfer_month"),
                DB::raw('COUNT(*) as transfer_count'),
                DB::raw('SUM(t.total_requested_qty) as requested_qty'),
                DB::raw('SUM(t.total_dispatched_qty) as dispatched_qty'),
                DB::raw('SUM(t.total_received_qty) as received_qty'),
                DB::raw('SUM(t.total_short_qty) as short_qty'),
                DB::raw('SUM(t.total_excess_qty) as excess_qty')
            )
            ->groupBy(DB::raw("DATE_FORMAT(t.transfer_date, '%Y-%m')"))
            ->orderBy('transfer_month')
            ->paginate(60);
    }

    public function exceptionSummary(array $filters = [])
    {
        return $this->baseTransferQuery($filters)
            ->leftJoin('stnew_stock_transfer_lines as l', 'l.transfer_id', '=', 't.id')
            ->select(
                't.id', 't.transfer_no', 't.transfer_date', 't.status', 't.priority',
                't.from_location_id', 't.to_location_id', 't.from_store_id', 't.to_store_id',
                DB::raw('SUM(COALESCE(l.short_qty,0)) as short_qty'),
                DB::raw('SUM(COALESCE(l.excess_qty,0)) as excess_qty'),
                DB::raw('TIMESTAMPDIFF(DAY, t.transfer_date, CURDATE()) as age_days')
            )
            ->where(function ($q) {
                $q->where('t.status', 'in_transit')
                    ->orWhere('t.status', 'returned_for_correction')
                    ->orWhere('t.status', 'rejected')
                    ->orWhere('t.total_short_qty', '>', 0)
                    ->orWhere('t.total_excess_qty', '>', 0)
                    ->orWhereRaw("TIMESTAMPDIFF(DAY, t.transfer_date, CURDATE()) > 3");
            })
            ->groupBy('t.id', 't.transfer_no', 't.transfer_date', 't.status', 't.priority', 't.from_location_id', 't.to_location_id', 't.from_store_id', 't.to_store_id')
            ->orderByDesc('age_days')
            ->paginate(100);
    }

    public function exportRows(string $type, array $filters = []): array
    {
        $map = [
            'product-wise' => 'productWise',
            'location-wise' => 'locationWise',
            'store-wise' => 'storeWise',
            'user-wise' => 'userWise',
            'vehicle-wise' => 'vehicleWise',
            'monthly-trend' => 'monthlyTrend',
            'exceptions' => 'exceptionSummary',
        ];
        $method = $map[$type] ?? null;
        if (!$method || !method_exists($this, $method)) {
            return [];
        }
        return $this->{$method}($filters)->getCollection()->map(fn ($row) => (array) $row)->all();
    }
}
