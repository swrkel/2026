<?php

namespace Modules\Loan\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class LoanLedgerService
{
    public function businessId(): ?int
    {
        return (int) (session('business.id') ?: session('user.business_id')) ?: null;
    }

    public function locationIdsForUser(): array
    {
        $user = auth()->user();
        if (!$user) {
            return [];
        }

        $businessId = $this->businessId();
        $ids = [];

        if (method_exists($user, 'permitted_locations')) {
            $permitted = $user->permitted_locations();
            if ($permitted !== 'all' && is_array($permitted)) {
                $ids = array_values(array_filter(array_map('intval', $permitted)));
            }
        }

        if (empty($ids) && Schema::hasTable('business_locations')) {
            $q = DB::table('business_locations');
            if ($businessId && Schema::hasColumn('business_locations', 'business_id')) {
                $q->where('business_id', $businessId);
            }
            if (Schema::hasColumn('business_locations', 'is_active')) {
                $q->where('is_active', 1);
            }
            $ids = $q->pluck('id')->map(fn ($id) => (int) $id)->toArray();
        }

        return $ids;
    }

    public function customers()
    {
        if (!Schema::hasTable('loan_customers')) {
            return collect();
        }

        $q = DB::table('loan_customers');
        $businessId = $this->businessId();
        if ($businessId && Schema::hasColumn('loan_customers', 'business_id')) {
            $q->where('business_id', $businessId);
        }

        return $q->orderBy('first_name')->get()->mapWithKeys(function ($c) {
            $name = trim(($c->first_name ?? '') . ' ' . ($c->last_name ?? ''));
            if ($name === '') {
                $name = $c->name ?? ('Customer #' . $c->id);
            }
            $code = $c->loan_customer_no ?? $c->customer_no ?? null;
            return [$c->id => trim(($code ? $code . ' - ' : '') . $name)];
        });
    }

    public function loansForDropdown()
    {
        if (!Schema::hasTable('loans')) {
            return collect();
        }

        $q = DB::table('loans');
        $businessId = $this->businessId();
        $this->applyBusinessAndLocationFilter($q, 'loans');

        return $q->orderByDesc('id')->get()->mapWithKeys(function ($loan) {
            $loanNo = $loan->loan_no ?? $loan->account_no ?? $loan->id;
            return [$loan->id => 'Loan #' . $loanNo];
        });
    }

    public function ledger(array $filters = [])
    {
        $rows = collect();

        if (!Schema::hasTable('loan_transactions')) {
            return collect();
        }

        $query = DB::table('loan_transactions as lt')
            ->leftJoin('loans as l', 'l.id', '=', 'lt.loan_id')
            ->select('lt.*', 'l.id as related_loan_id');

        $this->applyBusinessAndLocationFilter($query, 'lt', 'l');

        if (!empty($filters['customer_id'])) {
            $customerId = (int) $filters['customer_id'];
            if (Schema::hasTable('loan_customers') && Schema::hasColumn('loans', 'loan_customer_id')) {
                $query->where('l.loan_customer_id', $customerId);
            } elseif (Schema::hasColumn('loans', 'contact_id')) {
                $query->where('l.contact_id', $customerId);
            }
        }

        if (!empty($filters['loan_id'])) {
            $query->where('lt.loan_id', (int) $filters['loan_id']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate($this->dateColumn('lt'), '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate($this->dateColumn('lt'), '<=', $filters['date_to']);
        }

        $items = $query->orderBy($this->dateColumn('lt'))->orderBy('lt.id')->get();

        $balance = 0;
        foreach ($items as $item) {
            [$debit, $credit] = $this->debitCredit($item);
            $balance += $debit - $credit;
            $rows->push((object) [
                'transaction_date' => $this->formatDate($item->{$this->dateColumnName()} ?? $item->created_at ?? null),
                'system_date' => $this->formatDateTime($item->created_at ?? null),
                'reference_no' => $item->receipt_no ?? $item->reference ?? $item->ref_no ?? $item->id,
                'description' => $this->description($item),
                'debit' => $debit,
                'credit' => $credit,
                'balance' => $balance,
                'loan_id' => $item->loan_id ?? null,
            ]);
        }

        return $rows;
    }

    public function summary(array $filters = []): object
    {
        $rows = $this->ledger($filters);
        return (object) [
            'total_debit' => $rows->sum('debit'),
            'total_credit' => $rows->sum('credit'),
            'balance' => $rows->last()->balance ?? 0,
            'count' => $rows->count(),
        ];
    }

    private function applyBusinessAndLocationFilter($query, string $primaryAlias, ?string $loanAlias = null): void
    {
        $businessId = $this->businessId();
        $locationIds = $this->locationIdsForUser();
        $primaryTable = $primaryAlias === 'lt' ? 'loan_transactions' : $primaryAlias;

        if ($businessId) {
            if (Schema::hasColumn($primaryTable, 'business_id')) {
                $query->where($primaryAlias . '.business_id', $businessId);
            } elseif ($loanAlias && Schema::hasColumn('loans', 'business_id')) {
                $query->where($loanAlias . '.business_id', $businessId);
            }
        }

        if (!empty($locationIds)) {
            if (Schema::hasColumn($primaryTable, 'location_id')) {
                $query->whereIn($primaryAlias . '.location_id', $locationIds);
            } elseif ($loanAlias && Schema::hasColumn('loans', 'location_id')) {
                $query->whereIn($loanAlias . '.location_id', $locationIds);
            }
        }
    }

    private function dateColumn(string $alias): string
    {
        return Schema::hasColumn('loan_transactions', 'submitted_on') ? $alias . '.submitted_on' : $alias . '.created_at';
    }

    private function dateColumnName(): string
    {
        return Schema::hasColumn('loan_transactions', 'submitted_on') ? 'submitted_on' : 'created_at';
    }

    private function debitCredit($item): array
    {
        $amount = (float) ($item->amount ?? $item->principal ?? $item->credit ?? $item->debit ?? 0);
        $type = strtolower((string) ($item->type ?? $item->transaction_type ?? $item->name ?? ''));
        $loanTransactionType = (int) ($item->loan_transaction_type_id ?? 0);

        if (in_array($loanTransactionType, [1, 10, 11, 12], true) || str_contains($type, 'disbursement') || str_contains($type, 'fee') || str_contains($type, 'interest')) {
            return [$amount, 0];
        }

        if (in_array($loanTransactionType, [2, 4, 8, 9, 13], true) || str_contains($type, 'repayment') || str_contains($type, 'waive') || str_contains($type, 'recovery')) {
            return [0, $amount];
        }

        return [$amount, 0];
    }

    private function description($item): string
    {
        if (!empty($item->description)) {
            return $item->description;
        }
        if (!empty($item->name)) {
            return $item->name;
        }
        $types = [1 => 'Loan Disbursement', 2 => 'Repayment', 4 => 'Interest Waiver', 8 => 'Recovery Repayment', 9 => 'Fee Waiver', 10 => 'Fee Applied', 11 => 'Interest Applied', 12 => 'Loan Top Up', 13 => 'Loan Closed'];
        return $types[(int) ($item->loan_transaction_type_id ?? 0)] ?? 'Loan Transaction';
    }

    private function formatDate($value): string
    {
        return $value ? Carbon::parse($value)->format('Y-m-d') : '-';
    }

    private function formatDateTime($value): string
    {
        return $value ? Carbon::parse($value)->format('Y-m-d H:i') : '-';
    }
}
