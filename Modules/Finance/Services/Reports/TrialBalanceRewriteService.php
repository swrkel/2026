<?php

namespace Modules\Finance\Services\Reports;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TrialBalanceRewriteService
{
    private static $columnCache = [];

    public function rows(int $businessId, ?string $startDate, ?string $endDate, $locationId = null, ?string $search = null)
    {
        $startDate = $this->date($startDate) ?: Carbon::now()->format('Y-m-d');
        $endDate = $this->date($endDate) ?: $startDate;
        $locationId = $this->location($locationId);
        $search = trim((string) $search);

        $movement = DB::table('account_transactions as AT')
            ->join('accounts as BA', 'BA.id', '=', 'AT.account_id')
            ->where('BA.business_id', $businessId)
            ->whereNull('AT.deleted_at')
            ->whereDate('AT.operation_date', '>=', $startDate)
            ->whereDate('AT.operation_date', '<=', $endDate);

        if (! empty($locationId)) {
            $hasAtLocation = $this->hasColumn('account_transactions', 'location_id');

            $movement->leftJoin('transactions as T', 'AT.transaction_id', '=', 'T.id')
                ->where(function ($query) use ($locationId, $hasAtLocation) {
                    if ($hasAtLocation) {
                        $query->where('AT.location_id', $locationId)
                            ->orWhere('T.location_id', $locationId);
                    } else {
                        $query->where('T.location_id', $locationId);
                    }
                });
        }

        $movement->select('AT.account_id')
            ->selectRaw("COALESCE(SUM(CASE WHEN AT.type = 'debit' THEN AT.amount ELSE 0 END), 0) as debit_sum")
            ->selectRaw("COALESCE(SUM(CASE WHEN AT.type = 'credit' THEN AT.amount ELSE 0 END), 0) as credit_sum")
            ->groupBy('AT.account_id');

        return DB::table('accounts')
            ->leftJoin('account_types', 'accounts.account_type_id', '=', 'account_types.id')
            ->leftJoin('account_groups', 'accounts.asset_type', '=', 'account_groups.id')
            ->leftJoinSub($movement, 'movement', function ($join) {
                $join->on('movement.account_id', '=', 'accounts.id');
            })
            ->where('accounts.business_id', $businessId)
            ->where(function ($query) {
                $query->where('accounts.is_closed', 0)
                    ->orWhereNull('accounts.is_closed');
            })
            ->whereNull('accounts.deleted_at')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('accounts.name', 'like', '%' . $search . '%')
                        ->orWhere('accounts.account_number', 'like', '%' . $search . '%')
                        ->orWhere('account_types.name', 'like', '%' . $search . '%')
                        ->orWhere('account_groups.name', 'like', '%' . $search . '%');
                });
            })
            ->select(
                'accounts.id',
                'accounts.name',
                'accounts.account_number',
                'account_types.name as account_type_name',
                'account_groups.name as account_group_name',
                DB::raw('COALESCE(movement.debit_sum, 0) as debit_sum'),
                DB::raw('COALESCE(movement.credit_sum, 0) as credit_sum')
            )
            ->orderBy('accounts.account_number')
            ->orderBy('accounts.name')
            ->get()
            ->map(function ($account) {
                $debitSum = (float) $account->debit_sum;
                $creditSum = (float) $account->credit_sum;

                if ($this->normalSide($account->account_type_name) === 'credit') {
                    $balance = $creditSum - $debitSum;
                    $debit = $balance < 0 ? abs($balance) : 0;
                    $credit = $balance >= 0 ? $balance : 0;
                } else {
                    $balance = $debitSum - $creditSum;
                    $debit = $balance >= 0 ? $balance : 0;
                    $credit = $balance < 0 ? abs($balance) : 0;
                }

                $debit = round($debit, 2);
                $credit = round($credit, 2);

                if ($debit == 0.00 && $credit == 0.00) {
                    return null;
                }

                return (object) [
                    'account_number' => $account->account_number,
                    'name' => $account->name,
                    'debit_raw' => $debit,
                    'credit_raw' => $credit,
                    'debit' => $this->money($debit),
                    'credit' => $this->money($credit),
                ];
            })
            ->filter()
            ->values();
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

    private function hasColumn(string $table, string $column): bool
    {
        $connection = Schema::getConnection()->getName();
        $database = Schema::getConnection()->getDatabaseName();
        $key = $connection . '|' . $database . '|' . $table . '|' . $column;

        if (! array_key_exists($key, self::$columnCache)) {
            self::$columnCache[$key] = Schema::hasColumn($table, $column);
        }

        return self::$columnCache[$key];
    }

    private function date($value): ?string
    {
        if (empty($value)) {
            return null;
        }

        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function location($value)
    {
        return empty($value) || $value === 'all' ? null : $value;
    }

    private function money(float $amount): string
    {
        return '<span class="display_currency" data-currency_symbol="true">' . number_format($amount, 2, '.', '') . '</span>';
    }
}
