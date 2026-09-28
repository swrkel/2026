<?php

namespace Modules\PetroPD\Services;

use Illuminate\Support\Facades\DB;

/**
 * Petro PD Stable Recovery M2 V4
 *
 * Read-only duplicate audit helper for tenant databases.
 * This class deliberately does not delete records. It returns duplicate groups
 * so the UI/controller can warn the user or an admin can review safely.
 */
class PetroPdDuplicatePostingAuditService
{
    protected function tableExists(string $table): bool
    {
        return DB::getSchemaBuilder()->hasTable($table);
    }

    protected function columnsExist(string $table, array $columns): bool
    {
        foreach ($columns as $column) {
            if (!DB::getSchemaBuilder()->hasColumn($table, $column)) {
                return false;
            }
        }

        return true;
    }

    public function duplicateAccountTransactions(?int $businessId = null, int $limit = 100): array
    {
        if (!$this->tableExists('account_transactions')) {
            return [];
        }

        $columns = ['account_id', 'type', 'amount'];
        if (!$this->columnsExist('account_transactions', $columns)) {
            return [];
        }

        $groupColumns = ['account_id', 'type', 'amount'];
        foreach (['operation_date', 'sub_type', 'transaction_id', 'note'] as $optional) {
            if (DB::getSchemaBuilder()->hasColumn('account_transactions', $optional)) {
                $groupColumns[] = $optional;
            }
        }

        $query = DB::table('account_transactions')
            ->select($groupColumns)
            ->selectRaw('COUNT(*) as duplicate_count')
            ->groupBy($groupColumns)
            ->havingRaw('COUNT(*) > 1')
            ->limit($limit);

        if ($businessId && DB::getSchemaBuilder()->hasColumn('account_transactions', 'business_id')) {
            $query->where('business_id', $businessId);
        }

        return $query->get()->toArray();
    }

    public function duplicateCustomerLedgerRows(?int $businessId = null, int $limit = 100): array
    {
        foreach (['contact_ledger', 'customer_ledger', 'ledger_transactions'] as $table) {
            if ($this->tableExists($table)) {
                return $this->duplicateLedgerRowsFromTable($table, $businessId, $limit);
            }
        }

        return [];
    }

    protected function duplicateLedgerRowsFromTable(string $table, ?int $businessId, int $limit): array
    {
        $baseColumns = [];
        foreach (['contact_id', 'customer_id', 'type', 'amount', 'transaction_type', 'payment_type'] as $column) {
            if (DB::getSchemaBuilder()->hasColumn($table, $column)) {
                $baseColumns[] = $column;
            }
        }

        if (count($baseColumns) < 2) {
            return [];
        }

        foreach (['transaction_date', 'operation_date', 'ref_no', 'note'] as $optional) {
            if (DB::getSchemaBuilder()->hasColumn($table, $optional)) {
                $baseColumns[] = $optional;
            }
        }

        $query = DB::table($table)
            ->select($baseColumns)
            ->selectRaw('COUNT(*) as duplicate_count')
            ->groupBy($baseColumns)
            ->havingRaw('COUNT(*) > 1')
            ->limit($limit);

        if ($businessId && DB::getSchemaBuilder()->hasColumn($table, 'business_id')) {
            $query->where('business_id', $businessId);
        }

        return $query->get()->toArray();
    }
}
