<?php

namespace Modules\Loan\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class LoanStabilizationSummaryService
{
    protected function hasTable(string $table): bool
    {
        try {
            return Schema::hasTable($table);
        } catch (\Throwable $e) {
            return false;
        }
    }

    protected function hasColumn(string $table, string $column): bool
    {
        try {
            return Schema::hasColumn($table, $column);
        } catch (\Throwable $e) {
            return false;
        }
    }

    protected function loanBaseQuery($businessId = null, $locationId = null)
    {
        $query = DB::table('loans');

        if ($businessId && $this->hasColumn('loans', 'business_id')) {
            $query->where('business_id', $businessId);
        }

        if ($locationId && $this->hasColumn('loans', 'location_id')) {
            $query->where('location_id', $locationId);
        }

        return $query;
    }

    public function dashboardSummary($businessId = null, $locationId = null): array
    {
        if (!$this->hasTable('loans')) {
            return $this->emptySummary();
        }

        $base = $this->loanBaseQuery($businessId, $locationId);

        $totalLoans = (clone $base)->count();
        $activeLoans = $this->hasColumn('loans', 'status')
            ? (clone $base)->whereIn('status', ['active', 'approved', 'disbursed'])->count()
            : 0;
        $pendingLoans = $this->hasColumn('loans', 'status')
            ? (clone $base)->whereIn('status', ['draft', 'submitted', 'under_review', 'pending', 'pending_approval'])->count()
            : 0;
        $closedLoans = $this->hasColumn('loans', 'status')
            ? (clone $base)->whereIn('status', ['closed', 'settled', 'written_off'])->count()
            : 0;

        $principalColumn = $this->firstExistingColumn('loans', [
            'principal_amount',
            'approved_amount',
            'loan_amount',
            'applied_amount',
            'amount',
        ]);

        $portfolioAmount = $principalColumn
            ? (float) (clone $base)->sum($principalColumn)
            : 0.0;

        return [
            'total_loans' => $totalLoans,
            'active_loans' => $activeLoans,
            'pending_loans' => $pendingLoans,
            'closed_loans' => $closedLoans,
            'portfolio_amount' => $portfolioAmount,
            'arrears_accounts' => $this->arrearsAccounts($businessId, $locationId),
            'due_today' => $this->dueTodayCount($businessId, $locationId),
            'overdue_amount' => $this->overdueAmount($businessId, $locationId),
        ];
    }

    public function recentOperationalItems(int $limit = 15, $businessId = null, $locationId = null)
    {
        if (!$this->hasTable('loans')) {
            return collect();
        }

        $query = $this->loanBaseQuery($businessId, $locationId);

        if ($this->hasColumn('loans', 'created_at')) {
            $query->orderByDesc('created_at');
        } else {
            $query->orderByDesc('id');
        }

        return $query->limit($limit)->get();
    }

    public function arrearsAging($businessId = null, $locationId = null): array
    {
        if (!$this->hasTable('loan_repayment_schedules')) {
            return [
                '1_30' => ['count' => 0, 'amount' => 0.0],
                '31_60' => ['count' => 0, 'amount' => 0.0],
                '61_90' => ['count' => 0, 'amount' => 0.0],
                '90_plus' => ['count' => 0, 'amount' => 0.0],
            ];
        }

        $today = Carbon::today();
        $rows = $this->repaymentScheduleBase($businessId, $locationId)
            ->where('loan_repayment_schedules.due_date', '<', $today->toDateString())
            ->get();

        $buckets = [
            '1_30' => ['count' => 0, 'amount' => 0.0],
            '31_60' => ['count' => 0, 'amount' => 0.0],
            '61_90' => ['count' => 0, 'amount' => 0.0],
            '90_plus' => ['count' => 0, 'amount' => 0.0],
        ];

        foreach ($rows as $row) {
            $days = Carbon::parse($row->due_date)->diffInDays($today);
            $amount = (float) ($row->outstanding_amount ?? $row->total_due ?? 0);
            $bucket = $days <= 30 ? '1_30' : ($days <= 60 ? '31_60' : ($days <= 90 ? '61_90' : '90_plus'));
            $buckets[$bucket]['count']++;
            $buckets[$bucket]['amount'] += $amount;
        }

        return $buckets;
    }

    protected function repaymentScheduleBase($businessId = null, $locationId = null)
    {
        $query = DB::table('loan_repayment_schedules')
            ->leftJoin('loans', 'loan_repayment_schedules.loan_id', '=', 'loans.id');

        if ($businessId && $this->hasColumn('loans', 'business_id')) {
            $query->where('loans.business_id', $businessId);
        }

        if ($locationId && $this->hasColumn('loans', 'location_id')) {
            $query->where('loans.location_id', $locationId);
        }

        $dueExpression = $this->outstandingExpression();

        return $query->select(
            'loan_repayment_schedules.loan_id',
            'loan_repayment_schedules.due_date',
            DB::raw($dueExpression . ' as outstanding_amount'),
            'loan_repayment_schedules.total_due'
        )->whereRaw($dueExpression . ' > 0');
    }

    protected function outstandingExpression(): string
    {
        $table = 'loan_repayment_schedules';
        $parts = [];
        foreach (['principal', 'interest', 'fees', 'penalties'] as $col) {
            if ($this->hasColumn($table, $col)) {
                $parts[] = "COALESCE({$table}.{$col},0)";
            }
        }

        if (!$parts && $this->hasColumn($table, 'total_due')) {
            $parts[] = "COALESCE({$table}.total_due,0)";
        }

        foreach (['principal_repaid_derived', 'interest_repaid_derived', 'fees_repaid_derived', 'penalties_repaid_derived'] as $col) {
            if ($this->hasColumn($table, $col)) {
                $parts[] = "- COALESCE({$table}.{$col},0)";
            }
        }

        return $parts ? '(' . implode(' + ', $parts) . ')' : '0';
    }

    protected function arrearsAccounts($businessId = null, $locationId = null): int
    {
        if (!$this->hasTable('loan_repayment_schedules')) {
            return 0;
        }

        return (int) $this->repaymentScheduleBase($businessId, $locationId)
            ->where('loan_repayment_schedules.due_date', '<', Carbon::today()->toDateString())
            ->distinct('loan_repayment_schedules.loan_id')
            ->count('loan_repayment_schedules.loan_id');
    }

    protected function dueTodayCount($businessId = null, $locationId = null): int
    {
        if (!$this->hasTable('loan_repayment_schedules')) {
            return 0;
        }

        return (int) $this->repaymentScheduleBase($businessId, $locationId)
            ->whereDate('loan_repayment_schedules.due_date', Carbon::today()->toDateString())
            ->count();
    }

    protected function overdueAmount($businessId = null, $locationId = null): float
    {
        if (!$this->hasTable('loan_repayment_schedules')) {
            return 0.0;
        }

        return (float) $this->repaymentScheduleBase($businessId, $locationId)
            ->where('loan_repayment_schedules.due_date', '<', Carbon::today()->toDateString())
            ->get()
            ->sum('outstanding_amount');
    }

    protected function firstExistingColumn(string $table, array $columns): ?string
    {
        foreach ($columns as $column) {
            if ($this->hasColumn($table, $column)) {
                return $column;
            }
        }

        return null;
    }

    protected function emptySummary(): array
    {
        return [
            'total_loans' => 0,
            'active_loans' => 0,
            'pending_loans' => 0,
            'closed_loans' => 0,
            'portfolio_amount' => 0.0,
            'arrears_accounts' => 0,
            'due_today' => 0,
            'overdue_amount' => 0.0,
        ];
    }
}
