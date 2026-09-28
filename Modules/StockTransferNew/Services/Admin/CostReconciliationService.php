<?php

namespace Modules\StockTransferNew\Services\Admin;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CostReconciliationService
{
    public function summary(array $filters = []): array
    {
        $query = $this->baseQuery($filters);
        $data = DB::query()->fromSub($query, 'x')
            ->selectRaw('COUNT(*) as transfers')
            ->selectRaw('COALESCE(SUM(item_count),0) as items')
            ->selectRaw('COALESCE(SUM(transfer_cost),0) as transfer_cost')
            ->selectRaw('COALESCE(SUM(received_cost),0) as received_cost')
            ->selectRaw('COALESCE(SUM(cost_variance),0) as cost_variance')
            ->first();

        return [
            'transfers' => (int) ($data->transfers ?? 0),
            'items' => (int) ($data->items ?? 0),
            'transfer_cost' => (float) ($data->transfer_cost ?? 0),
            'received_cost' => (float) ($data->received_cost ?? 0),
            'cost_variance' => (float) ($data->cost_variance ?? 0),
        ];
    }

    public function rows(array $filters = [], int $limit = 500)
    {
        return $this->baseQuery($filters)
            ->orderByDesc('st.transaction_date')
            ->orderByDesc('st.id')
            ->limit($limit)
            ->get();
    }

    protected function baseQuery(array $filters = [])
    {
        $transferTable = Schema::hasTable('stock_transfer_new_transfers') ? 'stock_transfer_new_transfers' : 'stn_transfers';
        $lineTable = Schema::hasTable('stock_transfer_new_transfer_lines') ? 'stock_transfer_new_transfer_lines' : 'stn_transfer_lines';

        $query = DB::table($transferTable . ' as st')
            ->leftJoin($lineTable . ' as sl', 'sl.transfer_id', '=', 'st.id')
            ->leftJoin('business_locations as fl', 'fl.id', '=', 'st.from_location_id')
            ->leftJoin('business_locations as tl', 'tl.id', '=', 'st.to_location_id')
            ->leftJoin('stock_transfer_new_stores as ss', 'ss.id', '=', 'st.store_id')
            ->select([
                'st.id',
                'st.transaction_date',
                'st.transfer_no',
                'st.status',
                DB::raw('COALESCE(fl.name, "-") as from_location_name'),
                DB::raw('COALESCE(tl.name, "-") as to_location_name'),
                DB::raw('COALESCE(ss.name, "-") as store_name'),
            ])
            ->selectRaw('COUNT(sl.id) as item_count')
            ->selectRaw('COALESCE(SUM(COALESCE(sl.quantity,0) * COALESCE(sl.unit_cost,0)),0) as transfer_cost')
            ->selectRaw('COALESCE(SUM(COALESCE(sl.received_quantity, sl.quantity, 0) * COALESCE(sl.unit_cost,0)),0) as received_cost')
            ->selectRaw('COALESCE(SUM((COALESCE(sl.received_quantity, sl.quantity, 0) - COALESCE(sl.quantity,0)) * COALESCE(sl.unit_cost,0)),0) as cost_variance')
            ->groupBy('st.id', 'st.transaction_date', 'st.transfer_no', 'st.status', 'fl.name', 'tl.name', 'ss.name');

        foreach (['business_id', 'from_location_id', 'to_location_id', 'store_id', 'status'] as $field) {
            if (!empty($filters[$field])) {
                $query->where('st.' . $field, $filters[$field]);
            }
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('st.transaction_date', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->whereDate('st.transaction_date', '<=', $filters['date_to']);
        }

        return $query;
    }
}
