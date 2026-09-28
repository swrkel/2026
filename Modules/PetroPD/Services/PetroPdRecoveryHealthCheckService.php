<?php

namespace Modules\PetroPD\Services;

use Illuminate\Support\Facades\DB;

/**
 * Petro PD Stable Recovery M2 V5
 *
 * Tenant-safe read-only health checks for recovery testing.
 * This service does not insert/update/delete records. It is intended to be
 * called from controllers, artisan helpers, or temporary admin diagnostics
 * before and after settlement/payment testing.
 */
class PetroPdRecoveryHealthCheckService
{
    protected array $requiredTables = [
        'pump_operator_payments',
        'pump_operator_other_sales',
        'pumper_day_entries',
        'settlements',
        'settlement_card_payments',
        'settlement_cash_payments',
        'settlement_credit_sale_payments',
        'account_transactions',
    ];

    public function run(?int $businessId = null): array
    {
        return [
            'connection' => DB::connection()->getName(),
            'database' => DB::connection()->getDatabaseName(),
            'missing_tables' => $this->missingTables(),
            'table_columns' => $this->importantColumnMap(),
            'duplicate_settlements_by_shift' => $this->duplicateSettlementsByShift($businessId),
            'duplicate_operator_payments' => $this->duplicateOperatorPayments($businessId),
            'unfinalized_settlement_count' => $this->unfinalizedSettlementCount($businessId),
            'finalized_settlement_count' => $this->finalizedSettlementCount($businessId),
        ];
    }

    protected function schema(): \Illuminate\Database\Schema\Builder
    {
        return DB::getSchemaBuilder();
    }

    protected function hasTable(string $table): bool
    {
        return $this->schema()->hasTable($table);
    }

    protected function hasColumn(string $table, string $column): bool
    {
        return $this->hasTable($table) && $this->schema()->hasColumn($table, $column);
    }

    protected function missingTables(): array
    {
        return array_values(array_filter($this->requiredTables, fn ($table) => !$this->hasTable($table)));
    }

    protected function importantColumnMap(): array
    {
        $columns = [
            'pump_operator_payments' => ['id', 'business_id', 'shift_id', 'pump_operator_id', 'payment_type', 'amount', 'transaction_date', 'settlement_id', 'created_at'],
            'settlements' => ['id', 'business_id', 'shift_id', 'status', 'is_finalized', 'settlement_no', 'created_at'],
            'account_transactions' => ['id', 'business_id', 'account_id', 'transaction_id', 'type', 'amount', 'sub_type', 'operation_date', 'note'],
        ];

        $map = [];
        foreach ($columns as $table => $tableColumns) {
            $map[$table] = [
                'exists' => $this->hasTable($table),
                'columns' => [],
            ];
            foreach ($tableColumns as $column) {
                $map[$table]['columns'][$column] = $this->hasColumn($table, $column);
            }
        }

        return $map;
    }

    protected function duplicateSettlementsByShift(?int $businessId): array
    {
        if (!$this->hasTable('settlements') || !$this->hasColumn('settlements', 'shift_id')) {
            return [];
        }

        $group = ['shift_id'];
        if ($this->hasColumn('settlements', 'business_id')) {
            $group[] = 'business_id';
        }

        $query = DB::table('settlements')
            ->select($group)
            ->selectRaw('COUNT(*) as duplicate_count')
            ->whereNotNull('shift_id')
            ->groupBy($group)
            ->havingRaw('COUNT(*) > 1')
            ->orderByDesc('duplicate_count')
            ->limit(100);

        if ($businessId && $this->hasColumn('settlements', 'business_id')) {
            $query->where('business_id', $businessId);
        }

        return $query->get()->toArray();
    }

    protected function duplicateOperatorPayments(?int $businessId): array
    {
        if (!$this->hasTable('pump_operator_payments')) {
            return [];
        }

        $group = [];
        foreach (['business_id', 'shift_id', 'pump_operator_id', 'payment_type', 'amount'] as $column) {
            if ($this->hasColumn('pump_operator_payments', $column)) {
                $group[] = $column;
            }
        }

        if (count($group) < 3) {
            return [];
        }

        foreach (['transaction_date', 'created_by'] as $optional) {
            if ($this->hasColumn('pump_operator_payments', $optional)) {
                $group[] = $optional;
            }
        }

        $query = DB::table('pump_operator_payments')
            ->select($group)
            ->selectRaw('COUNT(*) as duplicate_count')
            ->groupBy($group)
            ->havingRaw('COUNT(*) > 1')
            ->orderByDesc('duplicate_count')
            ->limit(100);

        if ($businessId && $this->hasColumn('pump_operator_payments', 'business_id')) {
            $query->where('business_id', $businessId);
        }

        return $query->get()->toArray();
    }

    protected function unfinalizedSettlementCount(?int $businessId): int
    {
        return $this->settlementCountByFinalizedState($businessId, false);
    }

    protected function finalizedSettlementCount(?int $businessId): int
    {
        return $this->settlementCountByFinalizedState($businessId, true);
    }

    protected function settlementCountByFinalizedState(?int $businessId, bool $finalized): int
    {
        if (!$this->hasTable('settlements')) {
            return 0;
        }

        $query = DB::table('settlements');

        if ($businessId && $this->hasColumn('settlements', 'business_id')) {
            $query->where('business_id', $businessId);
        }

        if ($this->hasColumn('settlements', 'is_finalized')) {
            $query->where('is_finalized', $finalized ? 1 : 0);
        } elseif ($this->hasColumn('settlements', 'status')) {
            $query->{$finalized ? 'whereIn' : 'whereNotIn'}('status', ['finalized', 'finalised', 'posted']);
        }

        return (int) $query->count();
    }
}
