<?php

namespace Modules\Finance\Services\Accounts;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class FinanceAccountBalanceService
{
    public function getTrialBalanceAccounts(int $businessId, ?string $fromDate = null, ?string $toDate = null, $locationId = null)
    {
        $locationId = $this->normalizeLocationId($locationId);
        $fromDate = $this->normalizeDate($fromDate);
        $toDate = $this->normalizeDate($toDate);

        $movement = DB::table('account_transactions as AT')
            ->join('accounts as BA', 'BA.id', '=', 'AT.account_id')
            ->where('BA.business_id', $businessId)
            ->where(function ($openAccount) {
                $openAccount->where('BA.is_closed', 0)->orWhereNull('BA.is_closed');
            })
            ->whereNull('AT.deleted_at')
            ->where(function ($transactionBusiness) use ($businessId) {
                $transactionBusiness->where('AT.business_id', $businessId)
                    ->orWhereNull('AT.business_id')
                    ->orWhere('AT.business_id', 0);
            })
            ->when(! empty($fromDate), function ($query) use ($fromDate) {
                $query->whereDate('AT.operation_date', '>=', $fromDate);
            })
            ->when(! empty($toDate), function ($query) use ($toDate) {
                $query->whereDate('AT.operation_date', '<=', $toDate);
            });

        if (! empty($locationId)) {
            $movement->leftJoin('transactions as BT', 'AT.transaction_id', '=', 'BT.id')
                ->where(function ($locationScope) use ($locationId) {
                    $locationScope->where('BT.location_id', $locationId)
                        ->orWhere(function ($directPosting) use ($locationId) {
                            $directPosting->whereNull('BT.id')
                                ->where(function ($accountLocation) use ($locationId) {
                                    $accountLocation->where('BA.location_id', $locationId)
                                        ->orWhereNull('BA.location_id')
                                        ->orWhere('BA.location_id', 0);
                                });
                        });
                });
        }

        $movement->select('AT.account_id')
            ->selectRaw("COALESCE(SUM(CASE WHEN AT.type = 'debit' THEN AT.amount ELSE 0 END), 0) as debit_sum")
            ->selectRaw("COALESCE(SUM(CASE WHEN AT.type = 'credit' THEN AT.amount ELSE 0 END), 0) as credit_sum")
            ->groupBy('AT.account_id');

        return DB::table('accounts')
            ->leftJoin('account_types', 'accounts.account_type_id', '=', 'account_types.id')
            ->leftJoinSub($movement, 'movement', function ($join) {
                $join->on('movement.account_id', '=', 'accounts.id');
            })
            ->where('accounts.business_id', $businessId)
            ->where(function ($query) {
                $query->where('accounts.is_closed', 0)
                    ->orWhereNull('accounts.is_closed');
            })
            ->whereNull('accounts.deleted_at')
            ->select(
                'accounts.id',
                'accounts.name',
                'accounts.account_number',
                'accounts.location_id',
                'account_types.name as account_type_name',
                DB::raw('COALESCE(movement.debit_sum, 0) as debit_sum'),
                DB::raw('COALESCE(movement.credit_sum, 0) as credit_sum')
            )
            ->orderBy('accounts.name')
            ->get()
            ->map(function ($account) {
                $debitSum = (float) $account->debit_sum;
                $creditSum = (float) $account->credit_sum;
                $balance = $this->normalSide($account->account_type_name) === 'credit'
                    ? $creditSum - $debitSum
                    : $debitSum - $creditSum;

                $account->balance = round($balance, 4);
                $account->debit = $account->balance >= 0 ? $account->balance : 0;
                $account->credit = $account->balance < 0 ? abs($account->balance) : 0;

                unset($account->debit_sum, $account->credit_sum);

                return $account;
            })
            ->filter(function ($account) {
                return round(abs((float) $account->balance), 4) != 0.0000;
            })
            ->values();
    }

    /**
     * Return the requested parent account type and its direct child types.
     * This follows the same hierarchy depth used by the existing reports.
     */
    public function getAccountTypeIds(int $businessId, string $typeName): array
    {
        $parentIds = DB::table('account_types')
            ->where(function ($scope) use ($businessId) {
                $scope->where('business_id', $businessId)
                    ->orWhereNull('business_id');
            })
            ->whereRaw('LOWER(TRIM(name)) = ?', [strtolower(trim($typeName))])
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if ($parentIds === []) {
            return [];
        }

        // Include every descendant. Tenant charts may use parent -> group ->
        // sub-type hierarchies and some installations use global type rows.
        $ids = array_values(array_unique($parentIds));
        $frontier = $ids;

        while ($frontier !== []) {
            $children = DB::table('account_types')
                ->where(function ($scope) use ($businessId) {
                    $scope->where('business_id', $businessId)
                        ->orWhereNull('business_id');
                })
                ->whereIn('parent_account_type_id', $frontier)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();

            $children = array_values(array_diff(array_unique($children), $ids));
            if ($children === []) {
                break;
            }

            $ids = array_values(array_unique(array_merge($ids, $children)));
            $frontier = $children;
        }

        return $ids;
    }

    /**
     * Load a complete financial-statement section with one grouped movement
     * query instead of calling Account::getAccountBalance() for every row.
     */
    public function getStatementAccounts(
        int $businessId,
        array $accountTypeIds,
        string $normalSide,
        ?string $fromDate = null,
        ?string $toDate = null,
        $locationId = null
    ) {
        if (empty($accountTypeIds)) {
            return collect();
        }

        $locationId = $this->normalizeLocationId($locationId);
        $fromDate = $this->normalizeDate($fromDate);
        $toDate = $this->normalizeDate($toDate);

        $movement = DB::table('account_transactions as SAT')
            ->join('accounts as SCOPE_ACCOUNTS', 'SCOPE_ACCOUNTS.id', '=', 'SAT.account_id')
            ->where('SCOPE_ACCOUNTS.business_id', $businessId)
            ->whereNull('SAT.deleted_at')
            ->when(! empty($fromDate), function ($query) use ($fromDate) {
                $query->whereDate('SAT.operation_date', '>=', $fromDate);
            })
            ->when(! empty($toDate), function ($query) use ($toDate) {
                $query->whereDate('SAT.operation_date', '<=', $toDate);
            });

        if (! empty($locationId)) {
            // Transaction-linked postings use the transaction location. Direct
            // account postings (opening balances, fixed assets and journals)
            // have no transaction_id, so use the account location for those.
            $movement->leftJoin('transactions as STATEMENT_TX', 'STATEMENT_TX.id', '=', 'SAT.transaction_id')
                ->where(function ($locationScope) use ($locationId) {
                    $locationScope->where('STATEMENT_TX.location_id', $locationId)
                        ->orWhere(function ($directPosting) use ($locationId) {
                            $directPosting->whereNull('SAT.transaction_id')
                                ->where(function ($accountLocation) use ($locationId) {
                                    $accountLocation->where('SCOPE_ACCOUNTS.location_id', $locationId)
                                        ->orWhereNull('SCOPE_ACCOUNTS.location_id')
                                        ->orWhere('SCOPE_ACCOUNTS.location_id', 0);
                                });
                        });
                });
        }

        $movement->select('SAT.account_id')
            ->selectRaw("COALESCE(SUM(CASE WHEN SAT.type = 'debit' THEN SAT.amount ELSE 0 END), 0) as debit_sum")
            ->selectRaw("COALESCE(SUM(CASE WHEN SAT.type = 'credit' THEN SAT.amount ELSE 0 END), 0) as credit_sum")
            ->groupBy('SAT.account_id');

        $accounts = DB::table('accounts as STATEMENT_ACCOUNTS')
            ->leftJoin('business_locations as STATEMENT_LOCATIONS', 'STATEMENT_LOCATIONS.id', '=', 'STATEMENT_ACCOUNTS.location_id')
            ->leftJoinSub($movement, 'STATEMENT_MOVEMENT', function ($join) {
                $join->on('STATEMENT_MOVEMENT.account_id', '=', 'STATEMENT_ACCOUNTS.id');
            })
            ->where('STATEMENT_ACCOUNTS.business_id', $businessId)
            ->whereIn('STATEMENT_ACCOUNTS.account_type_id', $accountTypeIds)
            ->where(function ($openAccount) {
                $openAccount->where('STATEMENT_ACCOUNTS.is_closed', 0)
                    ->orWhereNull('STATEMENT_ACCOUNTS.is_closed');
            })
            ->whereNull('STATEMENT_ACCOUNTS.deleted_at')
            ->select([
                'STATEMENT_ACCOUNTS.id',
                'STATEMENT_ACCOUNTS.name',
                'STATEMENT_ACCOUNTS.account_number',
                'STATEMENT_ACCOUNTS.account_type_id',
                'STATEMENT_ACCOUNTS.location_id',
                'STATEMENT_LOCATIONS.name as location_name',
                DB::raw('COALESCE(STATEMENT_MOVEMENT.debit_sum, 0) as debit_sum'),
                DB::raw('COALESCE(STATEMENT_MOVEMENT.credit_sum, 0) as credit_sum'),
            ])
            ->orderBy('STATEMENT_ACCOUNTS.name')
            ->get();

        $creditNormal = strtolower(trim($normalSide)) === 'credit';

        return $accounts->map(function ($account) use ($creditNormal) {
            $debit = (float) $account->debit_sum;
            $credit = (float) $account->credit_sum;
            $account->balance = round($creditNormal ? ($credit - $debit) : ($debit - $credit), 2);
            unset($account->debit_sum, $account->credit_sum);

            return $account;
        });
    }

    /**
     * Backward-compatible single-account balance lookup.
     * Trial Balance itself uses the grouped query above; this method remains
     * available for any existing integrations that resolve this service.
     */
    public function getAccountBalance($account, int $businessId, ?string $fromDate = null, ?string $toDate = null, $locationId = null): float
    {
        $locationId = $this->normalizeLocationId($locationId);
        $fromDate = $this->normalizeDate($fromDate);
        $toDate = $this->normalizeDate($toDate);
        $accountId = (int) (is_object($account) ? ($account->id ?? 0) : $account);

        if ($accountId <= 0) {
            return 0.0;
        }

        $query = DB::table('account_transactions as AT')
            ->join('accounts as BA', 'BA.id', '=', 'AT.account_id')
            ->leftJoin('account_types as BAT', 'BA.account_type_id', '=', 'BAT.id')
            ->where('BA.business_id', $businessId)
            ->where('BA.id', $accountId)
            ->where('BA.is_closed', 0)
            ->whereNull('AT.deleted_at')
            ->when(! empty($fromDate), function ($builder) use ($fromDate) {
                $builder->whereDate('AT.operation_date', '>=', $fromDate);
            })
            ->when(! empty($toDate), function ($builder) use ($toDate) {
                $builder->whereDate('AT.operation_date', '<=', $toDate);
            });

        if (! empty($locationId)) {
            $query->join('transactions as BT', 'AT.transaction_id', '=', 'BT.id')
                ->where('BT.location_id', $locationId);
        }

        $movement = $query
            ->select('BAT.name as account_type_name')
            ->selectRaw("COALESCE(SUM(CASE WHEN AT.type = 'debit' THEN AT.amount ELSE 0 END), 0) as debit_sum")
            ->selectRaw("COALESCE(SUM(CASE WHEN AT.type = 'credit' THEN AT.amount ELSE 0 END), 0) as credit_sum")
            ->groupBy('BAT.name')
            ->first();

        if (empty($movement)) {
            return 0.0;
        }

        $debitSum = (float) $movement->debit_sum;
        $creditSum = (float) $movement->credit_sum;

        return $this->normalSide($movement->account_type_name) === 'credit'
            ? $creditSum - $debitSum
            : $debitSum - $creditSum;
    }

    private function normalSide(?string $accountTypeName): string
    {
        $name = strtolower(trim((string) $accountTypeName));
        $creditTypes = [
            'liabilities',
            'liability',
            'equity',
            'income',
            'long term liabilities',
            'current liabilities',
        ];

        return in_array($name, $creditTypes, true) ? 'credit' : 'debit';
    }

    private function normalizeLocationId($locationId)
    {
        if ($locationId === null || $locationId === '' || $locationId === 'all') {
            return null;
        }

        return $locationId;
    }

    private function normalizeDate($value): ?string
    {
        if (empty($value)) {
            return null;
        }

        if ($value instanceof CarbonInterface) {
            return $value->format('Y-m-d');
        }

        $value = trim((string) $value);

        try {
            if (preg_match('/^\d{4}-\d{2}-\d{2}/', $value, $matches)) {
                return Carbon::createFromFormat('Y-m-d', $matches[0])->format('Y-m-d');
            }

            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable $e) {
            return null;
        }
    }
}
