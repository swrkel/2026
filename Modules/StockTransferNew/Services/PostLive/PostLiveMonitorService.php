<?php

namespace Modules\StockTransferNew\Services\PostLive;

use Illuminate\Support\Facades\DB;

class PostLiveMonitorService
{
    public function summary(?int $businessId = null): array
    {
        $headers = $this->tableExists('stn_transfers') ? $this->scoped('stn_transfers', $businessId) : null;
        $audit = $this->tableExists('stn_activity_logs') ? $this->scoped('stn_activity_logs', $businessId) : null;

        return [
            'total_transfers' => $headers ? (clone $headers)->count() : 0,
            'draft_transfers' => $headers ? (clone $headers)->where('status', 'draft')->count() : 0,
            'pending_approval' => $headers ? (clone $headers)->whereIn('status', ['submitted', 'pending_approval'])->count() : 0,
            'in_transit' => $headers ? (clone $headers)->whereIn('status', ['dispatched', 'in_transit'])->count() : 0,
            'completed' => $headers ? (clone $headers)->whereIn('status', ['received', 'completed'])->count() : 0,
            'rejected' => $headers ? (clone $headers)->where('status', 'rejected')->count() : 0,
            'audit_events' => $audit ? (clone $audit)->count() : 0,
            'tables_ready' => $this->requiredTableStatus(),
        ];
    }

    public function exceptions(?int $businessId = null): array
    {
        $exceptions = [];

        if (!$this->tableExists('stn_transfers')) {
            return [['level' => 'danger', 'title' => 'Missing transfer table', 'message' => 'stn_transfers table is not available in this tenant database.']];
        }

        $slowHours = (int) config('stocktransfernew.post_live.slow_transfer_hours', 24);
        $slowCount = $this->scoped('stn_transfers', $businessId)
            ->whereIn('status', ['dispatched', 'in_transit'])
            ->where('updated_at', '<', now()->subHours($slowHours))
            ->count();

        if ($slowCount > 0) {
            $exceptions[] = [
                'level' => 'warning',
                'title' => 'Delayed in-transit transfers',
                'message' => $slowCount . ' transfer(s) have been in transit for more than ' . $slowHours . ' hours.',
            ];
        }

        $draftCount = $this->scoped('stn_transfers', $businessId)
            ->where('status', 'draft')
            ->where('updated_at', '<', now()->subDays(7))
            ->count();

        if ($draftCount > 0) {
            $exceptions[] = [
                'level' => 'info',
                'title' => 'Old drafts',
                'message' => $draftCount . ' draft transfer(s) are older than 7 days.',
            ];
        }

        if ($this->tableExists('stn_transfer_variances')) {
            $varianceCount = $this->scoped('stn_transfer_variances', $businessId)
                ->where(function ($query) {
                    $query->whereNull('resolved_at')->orWhere('status', '!=', 'resolved');
                })
                ->count();

            if ($varianceCount > 0) {
                $exceptions[] = [
                    'level' => 'warning',
                    'title' => 'Unresolved variances',
                    'message' => $varianceCount . ' transfer variance record(s) still need reconciliation.',
                ];
            }
        }

        return $exceptions ?: [[
            'level' => 'success',
            'title' => 'No immediate exceptions',
            'message' => 'No critical post-live transfer exceptions were detected.',
        ]];
    }

    public function latestActivity(?int $businessId = null, int $limit = 15): array
    {
        if (!$this->tableExists('stn_activity_logs')) {
            return [];
        }

        return $this->scoped('stn_activity_logs', $businessId)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(function ($row) {
                return [
                    'date' => (string) ($row->created_at ?? ''),
                    'action' => (string) ($row->action ?? $row->event ?? 'activity'),
                    'description' => (string) ($row->description ?? $row->remarks ?? ''),
                    'user_id' => $row->created_by ?? $row->user_id ?? null,
                ];
            })
            ->toArray();
    }

    public function requiredTableStatus(): array
    {
        $tables = [
            'stn_transfers',
            'stn_transfer_lines',
            'stn_stock_movements',
            'stn_approval_matrices',
            'stn_activity_logs',
            'stn_transfer_locks',
            'stn_scan_sessions',
        ];

        $status = [];
        foreach ($tables as $table) {
            $status[$table] = $this->tableExists($table);
        }
        return $status;
    }

    private function scoped(string $table, ?int $businessId)
    {
        $query = DB::table($table);
        if ($businessId && $this->columnExists($table, 'business_id')) {
            $query->where('business_id', $businessId);
        }
        return $query;
    }

    private function tableExists(string $table): bool
    {
        try {
            return DB::getSchemaBuilder()->hasTable($table);
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function columnExists(string $table, string $column): bool
    {
        try {
            return DB::getSchemaBuilder()->hasColumn($table, $column);
        } catch (\Throwable $e) {
            return false;
        }
    }
}
