<?php

namespace Modules\Finance\Services\Deposits;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Returns active Finance bank ledger accounts for the current business.
 */
class BankDepositAccountResolver
{
    /** @return Collection<int, string> */
    public function optionsForBusiness(int $businessId): Collection
    {
        if ($businessId <= 0) {
            return collect();
        }

        $baseQuery = DB::table('accounts as account')
            ->leftJoin('account_groups as account_group', 'account_group.id', '=', 'account.asset_type')
            ->leftJoin('account_types as account_type', 'account_type.id', '=', 'account.account_type_id')
            ->leftJoin(
                'account_types as parent_account_type',
                'parent_account_type.id',
                '=',
                'account_type.parent_account_type_id'
            )
            ->where('account.business_id', $businessId)
            ->whereNotIn(DB::raw('LOWER(TRIM(account.name))'), [
                'cash',
                'card',
                'cheques in hand',
                'post dated cheques',
                'issued post dated cheques',
            ]);

        if (Schema::hasColumn('accounts', 'deleted_at')) {
            $baseQuery->whereNull('account.deleted_at');
        }

        if (Schema::hasColumn('accounts', 'is_closed')) {
            $baseQuery->where(function ($active): void {
                $active->where('account.is_closed', 0)->orWhereNull('account.is_closed');
            });
        }

        $bankQuery = clone $baseQuery;
        $bankQuery->where(function ($bankAccount): void {
            $bankAccount
                ->whereIn(DB::raw('LOWER(TRIM(COALESCE(account_group.name, "")))'), [
                    'bank',
                    'bank account',
                    'bank accounts',
                ])
                ->orWhereRaw('LOWER(TRIM(COALESCE(account_group.name, ""))) LIKE ?', ['%bank%'])
                ->orWhereRaw('LOWER(TRIM(COALESCE(account_type.name, ""))) LIKE ?', ['%bank%'])
                ->orWhereRaw('LOWER(TRIM(COALESCE(parent_account_type.name, ""))) LIKE ?', ['%bank%']);
        });

        $options = $this->pluckOptions($bankQuery);
        if ($options->isNotEmpty()) {
            return $options;
        }

        // Compatibility fallback for older tenant databases where the account
        // group/type link is missing but bank ledger names are already present.
        $fallbackQuery = clone $baseQuery;
        $fallbackQuery->whereRaw('LOWER(TRIM(account.name)) LIKE ?', ['%bank%']);

        return $this->pluckOptions($fallbackQuery);
    }

    /** @return Collection<int, string> */
    private function pluckOptions($query): Collection
    {
        return $query
            ->select('account.id', 'account.name')
            ->distinct()
            ->orderBy('account.name')
            ->pluck('account.name', 'account.id');
    }
}
