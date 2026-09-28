<?php

namespace Modules\StockTransferNew\Services\TesterSupport;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CleanupPreviewService
{
    public function summary(array $filters = []): array
    {
        if (! Schema::hasTable('stn_transfers')) {
            return ['total_transfers' => 0, 'message' => 'stn_transfers table is missing.'];
        }

        $query = $this->filteredQuery($filters);

        return [
            'total_transfers' => (clone $query)->count(),
            'draft_transfers' => (clone $query)->where('status', 'draft')->count(),
            'cancelled_transfers' => (clone $query)->where('status', 'cancelled')->count(),
            'message' => 'Preview only. No records are deleted by this screen.',
        ];
    }

    public function transfers(array $filters = [])
    {
        if (! Schema::hasTable('stn_transfers')) {
            return collect();
        }

        return $this->filteredQuery($filters)
            ->select('id', 'transfer_no', 'business_id', 'status', 'created_at')
            ->orderByDesc('id')
            ->limit(100)
            ->get();
    }

    public function storeLog(string $note): void
    {
        if (! Schema::hasTable('stn_test_cleanup_logs')) {
            return;
        }

        DB::table('stn_test_cleanup_logs')->insert([
            'cleanup_note' => $note,
            'created_by' => auth()->id(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function filteredQuery(array $filters)
    {
        $query = DB::table('stn_transfers');

        if (! empty($filters['business_id'])) {
            $query->where('business_id', $filters['business_id']);
        }
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (! empty($filters['from_date'])) {
            $query->whereDate('created_at', '>=', $filters['from_date']);
        }
        if (! empty($filters['to_date'])) {
            $query->whereDate('created_at', '<=', $filters['to_date']);
        }

        return $query;
    }
}
