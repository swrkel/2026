<?php

namespace Modules\StockTransferNew\Services\Admin;

use Illuminate\Support\Facades\DB;

class DataQualityService
{
    public function summary(array $filters = []): array
    {
        return [
            'orphan_lines' => $this->countOrphanLines($filters),
            'missing_store_links' => $this->countMissingStoreLinks($filters),
            'negative_movements' => $this->countNegativeMovements($filters),
            'stuck_in_transit' => $this->countStuckInTransit($filters),
            'unbalanced_received' => $this->countUnbalancedReceived($filters),
            'latest_checks' => $this->latestChecks(),
        ];
    }

    protected function tenantWhere($query, array $filters)
    {
        foreach (['business_id', 'location_id', 'store_id'] as $field) {
            if (!empty($filters[$field])) {
                $query->where($field, $filters[$field]);
            }
        }

        if (!empty($filters['from_date'])) {
            $query->whereDate('created_at', '>=', $filters['from_date']);
        }

        if (!empty($filters['to_date'])) {
            $query->whereDate('created_at', '<=', $filters['to_date']);
        }

        return $query;
    }

    protected function countOrphanLines(array $filters): int
    {
        if (!DB::getSchemaBuilder()->hasTable('stn_transfer_lines')) {
            return 0;
        }

        $query = DB::table('stn_transfer_lines as l')
            ->leftJoin('stn_transfers as h', 'h.id', '=', 'l.transfer_id')
            ->whereNull('h.id');

        return (int) $this->tenantWhere($query, $filters)->count();
    }

    protected function countMissingStoreLinks(array $filters): int
    {
        if (!DB::getSchemaBuilder()->hasTable('stn_transfers')) {
            return 0;
        }

        $query = DB::table('stn_transfers')
            ->where(function ($q) {
                $q->whereNull('from_store_id')
                    ->orWhereNull('to_store_id')
                    ->orWhere('from_store_id', 0)
                    ->orWhere('to_store_id', 0);
            });

        return (int) $this->tenantWhere($query, $filters)->count();
    }

    protected function countNegativeMovements(array $filters): int
    {
        if (!DB::getSchemaBuilder()->hasTable('stn_stock_movements')) {
            return 0;
        }

        $query = DB::table('stn_stock_movements')->where('quantity', '<', 0);

        return (int) $this->tenantWhere($query, $filters)->count();
    }

    protected function countStuckInTransit(array $filters): int
    {
        if (!DB::getSchemaBuilder()->hasTable('stn_transfers')) {
            return 0;
        }

        $query = DB::table('stn_transfers')
            ->whereIn('status', ['dispatched', 'in_transit'])
            ->whereDate('updated_at', '<=', now()->subDays(3)->toDateString());

        return (int) $this->tenantWhere($query, $filters)->count();
    }

    protected function countUnbalancedReceived(array $filters): int
    {
        if (!DB::getSchemaBuilder()->hasTable('stn_transfer_lines')) {
            return 0;
        }

        $query = DB::table('stn_transfer_lines')
            ->whereRaw('COALESCE(received_qty, 0) > COALESCE(dispatched_qty, 0)');

        return (int) $query->count();
    }

    protected function latestChecks(): array
    {
        if (!DB::getSchemaBuilder()->hasTable('stn_data_quality_checks')) {
            return [];
        }

        return DB::table('stn_data_quality_checks')
            ->orderByDesc('checked_at')
            ->limit(10)
            ->get()
            ->map(fn ($row) => (array) $row)
            ->toArray();
    }

    public function exportCsv(array $filters = []): string
    {
        $summary = $this->summary($filters);
        $rows = [['Check', 'Count']];
        foreach ($summary as $key => $value) {
            if (is_array($value)) {
                continue;
            }
            $rows[] = [str_replace('_', ' ', ucwords($key, '_')), $value];
        }

        $out = fopen('php://temp', 'r+');
        foreach ($rows as $row) {
            fputcsv($out, $row);
        }
        rewind($out);
        return stream_get_contents($out) ?: '';
    }
}
