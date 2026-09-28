<?php

namespace Modules\Loan\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class LoanOperationalSummaryService
{
    public function summary(?int $businessId = null, ?int $locationId = null): array
    {
        $loans = $this->tableExists('loans') ? DB::table('loans') : null;
        $repayments = $this->tableExists('loan_repayments') ? DB::table('loan_repayments') : null;

        if ($loans) {
            $this->applyScope($loans, 'loans', $businessId, $locationId);
        }

        if ($repayments) {
            $this->applyScope($repayments, 'loan_repayments', $businessId, $locationId);
        }

        return [
            'total_loans' => $loans ? (clone $loans)->count() : 0,
            'active_loans' => $loans && Schema::hasColumn('loans', 'status') ? (clone $loans)->where('status', 'active')->count() : 0,
            'pending_loans' => $loans && Schema::hasColumn('loans', 'status') ? (clone $loans)->whereIn('status', ['draft', 'submitted', 'under_review', 'pending'])->count() : 0,
            'closed_loans' => $loans && Schema::hasColumn('loans', 'status') ? (clone $loans)->whereIn('status', ['closed', 'settled', 'written_off'])->count() : 0,
            'total_disbursed' => $loans ? $this->sumFirstAvailableColumn((clone $loans), 'loans', ['principal_amount', 'loan_amount', 'approved_amount', 'amount']) : 0,
            'total_repaid' => $repayments ? $this->sumFirstAvailableColumn((clone $repayments), 'loan_repayments', ['amount', 'paid_amount', 'total_amount']) : 0,
        ];
    }

    public function recentLoans(int $limit = 10, ?int $businessId = null, ?int $locationId = null)
    {
        if (!$this->tableExists('loans')) {
            return collect();
        }

        $query = DB::table('loans');
        $this->applyScope($query, 'loans', $businessId, $locationId);

        return $query->orderByDesc(Schema::hasColumn('loans', 'id') ? 'id' : 'created_at')
            ->limit($limit)
            ->get();
    }

    protected function applyScope($query, string $table, ?int $businessId = null, ?int $locationId = null): void
    {
        $businessId = $businessId ?: (int) session('business.id');

        if ($businessId && Schema::hasColumn($table, 'business_id')) {
            $query->where($table . '.business_id', $businessId);
        }

        if ($locationId && Schema::hasColumn($table, 'location_id')) {
            $query->where($table . '.location_id', $locationId);
        }
    }

    protected function sumFirstAvailableColumn($query, string $table, array $columns): float
    {
        foreach ($columns as $column) {
            if (Schema::hasColumn($table, $column)) {
                return (float) $query->sum($column);
            }
        }

        return 0.0;
    }

    protected function tableExists(string $table): bool
    {
        try {
            return Schema::hasTable($table);
        } catch (\Throwable $e) {
            return false;
        }
    }
}
