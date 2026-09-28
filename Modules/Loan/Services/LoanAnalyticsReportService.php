<?php

namespace Modules\Loan\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class LoanAnalyticsReportService
{
    public function filters(): array
    {
        $businessId = session('business.id') ?? session('user.business_id');

        $locations = collect();
        if (Schema::hasTable('business_locations')) {
            $locations = DB::table('business_locations')
                ->where('business_id', $businessId)
                ->orderBy('name')
                ->pluck('name', 'id');
        }

        $products = collect();
        if (Schema::hasTable('loan_products')) {
            $products = DB::table('loan_products')
                ->when(Schema::hasColumn('loan_products', 'business_id'), function ($q) use ($businessId) {
                    $q->where('business_id', $businessId);
                })
                ->orderBy('name')
                ->pluck('name', 'id');
        }

        $officers = collect();
        if (Schema::hasTable('users')) {
            $officers = DB::table('users')
                ->where('business_id', $businessId)
                ->orderBy('first_name')
                ->select('id', DB::raw("TRIM(CONCAT(COALESCE(first_name,''),' ',COALESCE(last_name,''))) as name"))
                ->pluck('name', 'id');
        }

        return compact('locations', 'products', 'officers');
    }

    public function dashboard(array $filters): array
    {
        $businessId = session('business.id') ?? session('user.business_id');
        $start = $filters['start_date'] ?? Carbon::now()->startOfMonth()->format('Y-m-d');
        $end = $filters['end_date'] ?? Carbon::now()->format('Y-m-d');

        $loans = DB::table('loans')
            ->when(Schema::hasColumn('loans', 'business_id'), function ($q) use ($businessId) {
                $q->where('business_id', $businessId);
            })
            ->when(!empty($filters['location_id']), fn($q) => $q->where('location_id', $filters['location_id']))
            ->when(!empty($filters['loan_product_id']), fn($q) => $q->where('loan_product_id', $filters['loan_product_id']))
            ->when(!empty($filters['loan_officer_id']), fn($q) => $q->where('loan_officer_id', $filters['loan_officer_id']));

        $applications = DB::table('loan_applications')
            ->when(Schema::hasColumn('loan_applications', 'business_id'), function ($q) use ($businessId) {
                $q->where('business_id', $businessId);
            })
            ->when(Schema::hasColumn('loan_applications', 'location_id') && !empty($filters['location_id']), fn($q) => $q->where('location_id', $filters['location_id']))
            ->whereBetween(DB::raw('DATE(created_at)'), [$start, $end]);

        $transactions = DB::table('loan_transactions')
            ->when(Schema::hasColumn('loan_transactions', 'business_id'), function ($q) use ($businessId) {
                $q->where('business_id', $businessId);
            })
            ->whereBetween(DB::raw('DATE(COALESCE(submitted_on, created_at))'), [$start, $end]);

        $disbursed = (clone $loans)
            ->whereIn('status', ['active', 'disbursed', 'closed', 'settled'])
            ->sum(DB::raw($this->existingColumn('loans', ['principal', 'approved_amount', 'loan_amount'], '0')));

        $repayments = (clone $transactions)
            ->where(function ($q) {
                if (Schema::hasColumn('loan_transactions', 'loan_transaction_type_id')) {
                    $q->where('loan_transaction_type_id', 2);
                } elseif (Schema::hasColumn('loan_transactions', 'type')) {
                    $q->whereIn('type', ['repayment', 'payment']);
                }
            })
            ->sum(DB::raw($this->repaymentAmountExpression()));

        $portfolio = [
            'total_applications' => (clone $applications)->count(),
            'approved_applications' => (clone $applications)->whereIn('status', ['approved', 'disbursed'])->count(),
            'rejected_applications' => (clone $applications)->where('status', 'rejected')->count(),
            'active_loans' => (clone $loans)->whereIn('status', ['active', 'disbursed'])->count(),
            'disbursed_amount' => (float) $disbursed,
            'repaid_amount' => (float) $repayments,
            'outstanding_amount' => max(0, (float) $disbursed - (float) $repayments),
        ];

        return [
            'portfolio' => $portfolio,
            'branchRows' => $this->branchRows($filters),
            'productRows' => $this->productRows($filters),
            'officerRows' => $this->officerRows($filters),
            'arrearsRows' => $this->arrearsRows($filters),
            'start_date' => $start,
            'end_date' => $end,
        ];
    }

    private function branchRows(array $filters)
    {
        if (!Schema::hasTable('business_locations')) {
            return collect();
        }
        $businessId = session('business.id') ?? session('user.business_id');
        return DB::table('loans')
            ->leftJoin('business_locations', 'loans.location_id', '=', 'business_locations.id')
            ->when(Schema::hasColumn('loans', 'business_id'), fn($q) => $q->where('loans.business_id', $businessId))
            ->when(!empty($filters['location_id']), fn($q) => $q->where('loans.location_id', $filters['location_id']))
            ->groupBy('business_locations.id', 'business_locations.name')
            ->selectRaw("COALESCE(business_locations.name, 'Not Assigned') as name, COUNT(loans.id) as total_loans, SUM(".$this->existingColumn('loans', ['principal', 'approved_amount', 'loan_amount'], '0').") as amount")
            ->orderBy('name')
            ->get();
    }

    private function productRows(array $filters)
    {
        if (!Schema::hasTable('loan_products')) {
            return collect();
        }
        $businessId = session('business.id') ?? session('user.business_id');
        return DB::table('loans')
            ->leftJoin('loan_products', 'loans.loan_product_id', '=', 'loan_products.id')
            ->when(Schema::hasColumn('loans', 'business_id'), fn($q) => $q->where('loans.business_id', $businessId))
            ->when(!empty($filters['loan_product_id']), fn($q) => $q->where('loans.loan_product_id', $filters['loan_product_id']))
            ->groupBy('loan_products.id', 'loan_products.name')
            ->selectRaw("COALESCE(loan_products.name, 'Not Assigned') as name, COUNT(loans.id) as total_loans, SUM(".$this->existingColumn('loans', ['principal', 'approved_amount', 'loan_amount'], '0').") as amount")
            ->orderBy('name')
            ->get();
    }

    private function officerRows(array $filters)
    {
        $businessId = session('business.id') ?? session('user.business_id');
        return DB::table('loans')
            ->leftJoin('users', 'loans.loan_officer_id', '=', 'users.id')
            ->when(Schema::hasColumn('loans', 'business_id'), fn($q) => $q->where('loans.business_id', $businessId))
            ->when(!empty($filters['loan_officer_id']), fn($q) => $q->where('loans.loan_officer_id', $filters['loan_officer_id']))
            ->groupBy('users.id', 'users.first_name', 'users.last_name')
            ->selectRaw("TRIM(CONCAT(COALESCE(users.first_name,''),' ',COALESCE(users.last_name,''))) as name, COUNT(loans.id) as total_loans, SUM(".$this->existingColumn('loans', ['principal', 'approved_amount', 'loan_amount'], '0').") as amount")
            ->orderBy('name')
            ->get();
    }

    private function arrearsRows(array $filters)
    {
        if (!Schema::hasTable('loan_repayment_schedules')) {
            return collect();
        }
        $businessId = session('business.id') ?? session('user.business_id');
        return DB::table('loan_repayment_schedules')
            ->join('loans', 'loan_repayment_schedules.loan_id', '=', 'loans.id')
            ->leftJoin('contacts', 'loans.contact_id', '=', 'contacts.id')
            ->leftJoin('business_locations', 'loans.location_id', '=', 'business_locations.id')
            ->when(Schema::hasColumn('loans', 'business_id'), fn($q) => $q->where('loans.business_id', $businessId))
            ->when(!empty($filters['location_id']), fn($q) => $q->where('loans.location_id', $filters['location_id']))
            ->whereDate('loan_repayment_schedules.due_date', '<', Carbon::today()->format('Y-m-d'))
            ->where('loan_repayment_schedules.total_due', '>', 0)
            ->selectRaw("loans.id as loan_id, COALESCE(contacts.name, CONCAT('Customer #', loans.contact_id)) as customer_name, COALESCE(business_locations.name, '-') as location_name, MIN(loan_repayment_schedules.due_date) as oldest_due_date, SUM(loan_repayment_schedules.total_due) as overdue_amount")
            ->groupBy('loans.id', 'contacts.name', 'loans.contact_id', 'business_locations.name')
            ->orderByDesc('overdue_amount')
            ->limit(50)
            ->get();
    }

    private function repaymentAmountExpression(): string
    {
        $columns = ['amount', 'principal_repaid_derived', 'principal_portion_derived'];
        $parts = [];
        foreach ($columns as $column) {
            if (Schema::hasColumn('loan_transactions', $column)) {
                $parts[] = "COALESCE($column,0)";
            }
        }
        return empty($parts) ? '0' : implode(' + ', $parts);
    }

    private function existingColumn(string $table, array $columns, string $fallback): string
    {
        foreach ($columns as $column) {
            if (Schema::hasColumn($table, $column)) {
                return "COALESCE($table.$column,0)";
            }
        }
        return $fallback;
    }
}
