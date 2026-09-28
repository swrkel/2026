<?php

namespace Modules\Chequer\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

trait Concerns
{
    protected function businessId()
    {
        return request()->session()->get('user.business_id') ?: request()->session()->get('business.id') ?: 1;
    }

    protected function tableReady($table)
    {
        try { return Schema::hasTable($table); } catch (\Throwable $e) { return false; }
    }

    protected function columnReady($table, $column)
    {
        try { return Schema::hasTable($table) && Schema::hasColumn($table, $column); } catch (\Throwable $e) { return false; }
    }

    protected function safeCount($table, array $where = [])
    {
        try {
            if (!$this->tableReady($table)) { return 0; }
            $q = DB::table($table);
            foreach ($where as $key => $value) {
                if ($this->columnReady($table, $key)) {
                    $q->where($key, $value);
                }
            }
            return (int) $q->count();
        } catch (\Throwable $e) {
            \Log::warning('Chequer safeCount fallback', ['table' => $table, 'message' => $e->getMessage()]);
            return 0;
        }
    }

    protected function safeMoney($value)
    {
        return number_format((float) $value, 2, '.', ',');
    }

    protected function bankAccountsQuery()
    {
        $business_id = $this->businessId();

        $q = DB::table('accounts')->where('accounts.business_id', $business_id);

        if ($this->tableReady('account_groups')) {
            $q->leftJoin('account_groups', function ($join) {
                $join->on('accounts.asset_type', '=', 'account_groups.id');
                if (Schema::hasColumn('account_groups', 'business_id')) {
                    $join->on('accounts.business_id', '=', 'account_groups.business_id');
                }
            });

            $q->where(function ($query) {
                $query->where('accounts.asset_type', 4)
                    ->orWhere('account_groups.name', 'like', '%Bank%');
            });
        } else {
            // Fallback when account group metadata is not available in a tenant database.
            $q->where('accounts.asset_type', 4);
        }

        if ($this->columnReady('accounts', 'is_closed')) {
            $q->where(function ($query) {
                $query->whereNull('accounts.is_closed')->orWhere('accounts.is_closed', 0);
            });
        }

        if ($this->columnReady('accounts', 'deleted_at')) {
            $q->whereNull('accounts.deleted_at');
        }

        $select = [
            'accounts.id',
            'accounts.name',
            'accounts.account_number',
            'accounts.note',
            'accounts.asset_type',
        ];

        if ($this->tableReady('account_groups')) {
            $select[] = 'account_groups.name as account_group';
        }

        return $q->select($select)->orderBy('accounts.name');
    }

    protected function bankAccountsCount()
    {
        try {
            if (!$this->tableReady('accounts')) { return 0; }
            return (int) $this->bankAccountsQuery()->count();
        } catch (\Throwable $e) {
            \Log::warning('Chequer bank account count fallback', ['message' => $e->getMessage()]);
            return 0;
        }
    }

    protected function bankAccountsForDropdown()
    {
        try {
            if (!$this->tableReady('accounts')) { return collect(); }
            return $this->bankAccountsQuery()
                ->get()
                ->mapWithKeys(function ($account) {
                    $label = $account->name;
                    if (!empty($account->account_number)) {
                        $label .= ' - ' . $account->account_number;
                    }
                    return [$account->id => $label];
                });
        } catch (\Throwable $e) {
            \Log::warning('Chequer bank account dropdown fallback', ['message' => $e->getMessage()]);
            return collect();
        }
    }
}
