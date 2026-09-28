<?php

namespace Modules\StockTransferNew\Services\Admin;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Pagination\LengthAwarePaginator;

class OperationsReviewService
{
    public function summary(int $businessId, array $filters = []): array
    {
        $rows = $this->buildFindings($businessId, $filters);
        return [
            'critical' => collect($rows)->where('severity', 'critical')->count(),
            'high' => collect($rows)->where('severity', 'high')->count(),
            'medium' => collect($rows)->where('severity', 'medium')->count(),
            'low' => collect($rows)->where('severity', 'low')->count(),
            'total' => count($rows),
        ];
    }

    public function findings(int $businessId, array $filters = [], bool $paginate = true)
    {
        $rows = collect($this->buildFindings($businessId, $filters));
        if (! empty($filters['severity'])) {
            $rows = $rows->where('severity', $filters['severity'])->values();
        }

        if (! $paginate) {
            return $rows->values()->all();
        }

        $page = max(1, (int) request()->input('page', 1));
        $perPage = 25;
        return new LengthAwarePaginator(
            $rows->forPage($page, $perPage)->values(),
            $rows->count(),
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );
    }

    protected function buildFindings(int $businessId, array $filters): array
    {
        $findings = [];
        if (! Schema::hasTable('stn_transfers')) {
            return [[
                'severity' => 'critical',
                'area' => 'installation',
                'reference' => 'stn_transfers',
                'issue' => 'Main transfer table is missing in the tenant database.',
                'recommendation' => 'Run the StockTransferNew tenant create-table SQL before testing.',
            ]];
        }

        $query = DB::table('stn_transfers')->where('business_id', $businessId);
        if (! empty($filters['from_date'])) {
            $query->whereDate('created_at', '>=', $filters['from_date']);
        }
        if (! empty($filters['to_date'])) {
            $query->whereDate('created_at', '<=', $filters['to_date']);
        }
        foreach (['location_id', 'store_id'] as $field) {
            if (! empty($filters[$field]) && Schema::hasColumn('stn_transfers', $field)) {
                $query->where($field, $filters[$field]);
            }
        }

        $staleDrafts = (clone $query)->where('status', 'draft')->where('created_at', '<', now()->subDays(7))->limit(50)->get();
        foreach ($staleDrafts as $transfer) {
            $findings[] = $this->finding('medium', 'workflow', $transfer->transfer_no ?? $transfer->id, 'Draft transfer is older than 7 days.', 'Review and submit, cancel, or delete the old draft after checking with the user.');
        }

        $inTransit = (clone $query)->where('status', 'in_transit')->where('updated_at', '<', now()->subDays(3))->limit(50)->get();
        foreach ($inTransit as $transfer) {
            $findings[] = $this->finding('high', 'dispatch/receive', $transfer->transfer_no ?? $transfer->id, 'Transfer is in transit for more than 3 days.', 'Check vehicle/store handover and complete receiving or record remarks.');
        }

        if (Schema::hasTable('stn_transfer_lines')) {
            $zeroQty = DB::table('stn_transfer_lines as l')
                ->join('stn_transfers as t', 't.id', '=', 'l.transfer_id')
                ->where('t.business_id', $businessId)
                ->where(function ($q) { $q->whereNull('l.quantity')->orWhere('l.quantity', '<=', 0); })
                ->limit(50)->get(['t.transfer_no', 't.id', 'l.product_id']);
            foreach ($zeroQty as $line) {
                $findings[] = $this->finding('critical', 'data quality', $line->transfer_no ?? $line->id, 'Transfer line has zero or empty quantity.', 'Correct the line quantity before approval/dispatch.');
            }
        }

        if (Schema::hasTable('stn_transfer_locks')) {
            $oldLocks = DB::table('stn_transfer_locks')->where('business_id', $businessId)->where('created_at', '<', now()->subHours(6))->limit(50)->get();
            foreach ($oldLocks as $lock) {
                $findings[] = $this->finding('medium', 'security locks', $lock->transfer_id ?? $lock->id, 'Old transfer lock remains active for more than 6 hours.', 'Use the safe diagnostics screen before releasing any lock.');
            }
        }

        if (empty($findings)) {
            $findings[] = $this->finding('low', 'review', 'OK', 'No major operational issues found for the selected filters.', 'Continue normal monitoring.');
        }

        return $findings;
    }

    protected function finding(string $severity, string $area, $reference, string $issue, string $recommendation): array
    {
        return compact('severity', 'area', 'reference', 'issue', 'recommendation');
    }
}
