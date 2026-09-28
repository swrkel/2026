<?php

namespace Modules\PetroPD\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Non-destructive production-readiness verification for PetroPD payment integrity.
 *
 * This service never updates or deletes financial data. It verifies the schema,
 * database guards, authoritative master/detail ownership and optionally one exact
 * business/operator/Shift snapshot before deployment or settlement finalization.
 */
class PetroPdPaymentIntegrityReadinessService
{
    private const MASTER_COLUMNS = [
        'id',
        'business_id',
        'pump_operator_id',
        'shift_id',
        'payment_type',
        'payment_amount',
        'gross_amount',
        'discount_amount',
        'net_amount',
        'source_type',
        'source_id',
        'customer_id',
        'transaction_date',
        'reference_no',
    ];

    private const AUTHORITY_TABLES = [
        'settlement_cash_payments' => 'pump_operator_id',
        'settlement_card_payments' => 'pump_operator_id',
        'settlement_cheque_payments' => 'pump_operator_id',
        'settlement_credit_sale_payments' => 'pump_operator_id',
        'settlement_shortage_payments' => 'pump_operator_id',
        'settlement_excess_payments' => 'pump_operator_id',
        'daily_collections' => 'pump_operator_id',
        'daily_cards' => 'pump_operator_id',
        'daily_cheque_payments' => 'pump_operator_id',
        'daily_vouchers' => 'operator_id',
    ];

    private const REQUIRED_AUDIT_TABLES = [
        'petro_pd_payment_reconciliation_events',
        'petro_pd_payment_integrity_repair_runs',
        'petro_pd_payment_integrity_repair_actions',
    ];

    private const REQUIRED_TRIGGERS = [
        'trg_pop_authority_bi_20260723',
        'trg_pop_authority_bu_20260723',
    ];
    public function __construct(
        private readonly PetroPdSettlementPaymentSnapshotService $snapshotService
    ) {
    }

    /**
     * @param array{business_id?:int|null,pump_operator_id?:int|null,shift_id?:int|null,settlement_id?:int|null,settlement_no?:string|null} $scope
     * @return array{ready:bool,blocking_count:int,warning_count:int,checks:array<int,array<string,mixed>>,scope_snapshot:?array<string,mixed>}
     */
    public function verify(array $scope = []): array
    {
        $checks = [];

        $this->checkMasterSchema($checks);
        $this->checkAuthoritySchema($checks);
        $this->checkAuditSchema($checks);
        $this->checkDatabaseTriggers($checks);
        $this->checkMasterData($checks, $scope);
        $this->checkAuthorityData($checks, $scope);
        $this->checkOpenReconciliationEvents($checks, $scope);

        $scopeSnapshot = $this->checkScopeSnapshot($checks, $scope);

        $blocking = array_values(array_filter(
            $checks,
            fn (array $check) => ($check['status'] ?? '') === 'FAIL'
        ));
        $warnings = array_values(array_filter(
            $checks,
            fn (array $check) => ($check['status'] ?? '') === 'WARN'
        ));

        return [
            'ready' => empty($blocking),
            'blocking_count' => count($blocking),
            'warning_count' => count($warnings),
            'checks' => $checks,
            'scope_snapshot' => $scopeSnapshot,
        ];
    }

    private function checkMasterSchema(array &$checks): void
    {
        if (! Schema::hasTable('pump_operator_payments')) {
            $this->add($checks, 'master_table', 'FAIL', 'pump_operator_payments table is missing.');
            return;
        }

        $missing = array_values(array_filter(
            self::MASTER_COLUMNS,
            fn (string $column) => ! Schema::hasColumn('pump_operator_payments', $column)
        ));

        $this->add(
            $checks,
            'master_columns',
            empty($missing) ? 'PASS' : 'FAIL',
            empty($missing)
                ? 'All authoritative payment columns are present.'
                : 'Missing authoritative columns: ' . implode(', ', $missing),
            ['missing_columns' => $missing]
        );
    }

    private function checkAuthoritySchema(array &$checks): void
    {
        foreach (self::AUTHORITY_TABLES as $table => $operatorColumn) {
            if (! Schema::hasTable($table)) {
                $this->add($checks, "schema:{$table}", 'WARN', "Optional/legacy table {$table} is not present on this tenant.");
                continue;
            }

            $required = ['business_id', 'pump_payment_id', 'shift_id', $operatorColumn];
            $missing = array_values(array_filter(
                array_unique($required),
                fn (string $column) => ! Schema::hasColumn($table, $column)
            ));

            $this->add(
                $checks,
                "schema:{$table}",
                empty($missing) ? 'PASS' : 'FAIL',
                empty($missing)
                    ? "{$table} contains the master-link and immutable Shift columns."
                    : "{$table} is missing: " . implode(', ', $missing),
                ['missing_columns' => $missing]
            );
        }
    }

    private function checkAuditSchema(array &$checks): void
    {
        foreach (self::REQUIRED_AUDIT_TABLES as $table) {
            $this->add(
                $checks,
                "audit_table:{$table}",
                Schema::hasTable($table) ? 'PASS' : 'FAIL',
                Schema::hasTable($table)
                    ? "{$table} is available."
                    : "{$table} is missing; reconciliation/audit history cannot be trusted."
            );
        }
    }

    private function checkDatabaseTriggers(array &$checks): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            $this->add($checks, 'database_triggers', 'WARN', 'Trigger verification is available only for MySQL tenant databases.');
            return;
        }

        try {
            $requiredTriggers = self::REQUIRED_TRIGGERS;
            foreach (array_keys(self::AUTHORITY_TABLES) as $table) {
                if (! Schema::hasTable($table)
                    || ! Schema::hasColumn($table, 'pump_payment_id')
                    || ! Schema::hasColumn($table, 'shift_id')) {
                    continue;
                }

                $shortName = $this->triggerShortName($table);
                $requiredTriggers[] = "trg_{$shortName}_authority_bi_20260723";
                $requiredTriggers[] = "trg_{$shortName}_authority_bu_20260723";
            }

            $existing = DB::table('information_schema.triggers')
                ->where('trigger_schema', DB::connection()->getDatabaseName())
                ->whereIn('trigger_name', $requiredTriggers)
                ->pluck('trigger_name')
                ->all();

            $missing = array_values(array_diff($requiredTriggers, $existing));
            $this->add(
                $checks,
                'database_triggers',
                empty($missing) ? 'PASS' : 'FAIL',
                empty($missing)
                    ? 'All master/detail Shift and duplicate guards are active.'
                    : 'Missing database guards: ' . implode(', ', $missing),
                ['missing_triggers' => $missing]
            );
        } catch (Throwable $e) {
            $this->add($checks, 'database_triggers', 'FAIL', 'Unable to verify database triggers: ' . $e->getMessage());
        }
    }

    private function checkMasterData(array &$checks, array $scope): void
    {
        if (! Schema::hasTable('pump_operator_payments')) {
            return;
        }

        $recognizedTypes = ['cash', 'card', 'cards', 'cheque', 'cheques', 'credit', 'multiple_credit', 'other', 'shortage', 'excess'];
        $query = DB::table('pump_operator_payments')
            ->whereNotNull('pump_operator_id')
            ->where('pump_operator_id', '>', 0)
            ->whereIn('payment_type', $recognizedTypes)
            ->where(function ($q) {
                $q->whereNull('shift_id')->orWhere('shift_id', 0);
            });
        $this->applyMasterScope($query, $scope);
        $missingShift = (int) $query->count();

        $this->add(
            $checks,
            'master_missing_shift',
            $missingShift === 0 ? 'PASS' : 'FAIL',
            $missingShift === 0
                ? 'Every scoped Pump Operator Payment has one immutable Shift ID.'
                : "{$missingShift} master payment(s) have no valid Shift ID.",
            ['count' => $missingShift]
        );

        if (Schema::hasColumn('pump_operator_payments', 'source_type')
            && Schema::hasColumn('pump_operator_payments', 'source_id')) {
            $duplicateSourceQuery = DB::table('pump_operator_payments')
                ->select('business_id', 'source_type', 'source_id')
                ->whereNotNull('source_type')
                ->where('source_type', '<>', '')
                ->whereNotNull('source_id')
                ->where('source_id', '>', 0);
            $this->applyMasterScope($duplicateSourceQuery, $scope);
            $duplicateSourceCount = (int) DB::query()->fromSub(
                $duplicateSourceQuery
                    ->groupBy('business_id', 'source_type', 'source_id')
                    ->havingRaw('COUNT(*) > 1'),
                'duplicate_sources'
            )->count();

            $this->add(
                $checks,
                'duplicate_master_source_identity',
                $duplicateSourceCount === 0 ? 'PASS' : 'FAIL',
                $duplicateSourceCount === 0
                    ? 'No duplicate authoritative payment source identities were found.'
                    : "{$duplicateSourceCount} duplicate master source identity group(s) were found.",
                ['count' => $duplicateSourceCount]
            );
        }
    }

    private function checkAuthorityData(array &$checks, array $scope): void
    {
        if (! Schema::hasTable('pump_operator_payments')) {
            return;
        }

        foreach (self::AUTHORITY_TABLES as $table => $operatorColumn) {
            if (! Schema::hasTable($table)
                || ! Schema::hasColumn($table, 'pump_payment_id')
                || ! Schema::hasColumn($table, 'shift_id')
                || ! Schema::hasColumn($table, 'business_id')) {
                continue;
            }

            $base = DB::table($table . ' as detail')
                ->whereNotNull('detail.pump_payment_id')
                ->where('detail.pump_payment_id', '>', 0);
            $this->applyDetailScope($base, $scope, $table, 'detail', $operatorColumn);

            $duplicateQuery = clone $base;
            $duplicateCount = (int) DB::query()->fromSub(
                $duplicateQuery
                    ->select('detail.business_id', 'detail.pump_payment_id')
                    ->groupBy('detail.business_id', 'detail.pump_payment_id')
                    ->havingRaw('COUNT(*) > 1'),
                'duplicate_details'
            )->count();

            $orphanQuery = clone $base;
            $orphanCount = (int) $orphanQuery
                ->leftJoin('pump_operator_payments as pop', 'pop.id', '=', 'detail.pump_payment_id')
                ->whereNull('pop.id')
                ->count();

            $hasOperatorColumn = Schema::hasColumn($table, $operatorColumn);
            $mismatchQuery = DB::table($table . ' as detail')
                ->join('pump_operator_payments as pop', 'pop.id', '=', 'detail.pump_payment_id')
                ->where(function ($q) use ($operatorColumn, $hasOperatorColumn) {
                    $q->whereNull('detail.business_id')
                        ->orWhere('detail.business_id', 0)
                        ->orWhereColumn('detail.business_id', '<>', 'pop.business_id')
                        ->orWhereNull('detail.shift_id')
                        ->orWhere('detail.shift_id', 0)
                        ->orWhereColumn('detail.shift_id', '<>', 'pop.shift_id');

                    if ($hasOperatorColumn) {
                        $q->orWhereNull("detail.{$operatorColumn}")
                            ->orWhere("detail.{$operatorColumn}", 0)
                            ->orWhereColumn("detail.{$operatorColumn}", '<>', 'pop.pump_operator_id');
                    }
                });
            $this->applyDetailScope($mismatchQuery, $scope, $table, 'detail', $operatorColumn);
            $mismatchCount = (int) $mismatchQuery->count();

            $status = ($duplicateCount + $orphanCount + $mismatchCount) === 0 ? 'PASS' : 'FAIL';
            $this->add(
                $checks,
                "data:{$table}",
                $status,
                $status === 'PASS'
                    ? "{$table} has one valid detail row per authoritative payment and matching ownership scope."
                    : "{$table}: duplicates={$duplicateCount}, orphan links={$orphanCount}, scope mismatches={$mismatchCount}.",
                [
                    'duplicate_groups' => $duplicateCount,
                    'orphan_links' => $orphanCount,
                    'scope_mismatches' => $mismatchCount,
                ]
            );
        }
    }

    private function checkOpenReconciliationEvents(array &$checks, array $scope): void
    {
        if (! Schema::hasTable('petro_pd_payment_reconciliation_events')) {
            return;
        }

        $query = DB::table('petro_pd_payment_reconciliation_events')
            ->whereNull('resolved_at')
            ->where('severity', 'critical');

        if (! empty($scope['business_id'])) {
            $query->where('business_id', (int) $scope['business_id']);
        }
        if (! empty($scope['pump_operator_id'])) {
            $query->where('pump_operator_id', (int) $scope['pump_operator_id']);
        }
        if (! empty($scope['settlement_id'])) {
            $query->where('settlement_id', (int) $scope['settlement_id']);
        }
        if (! empty($scope['settlement_no'])) {
            $query->where('settlement_no', (string) $scope['settlement_no']);
        }
        if (! empty($scope['shift_id'])) {
            $needle = (string) (int) $scope['shift_id'];
            $query->where(function ($q) use ($needle) {
                $q->where('shift_ids', $needle)
                    ->orWhere('shift_ids', 'like', $needle . ',%')
                    ->orWhere('shift_ids', 'like', '%,' . $needle)
                    ->orWhere('shift_ids', 'like', '%,' . $needle . ',%');
            });
        }

        $count = (int) $query->count();
        $this->add(
            $checks,
            'unresolved_critical_events',
            $count === 0 ? 'PASS' : 'FAIL',
            $count === 0
                ? 'No unresolved critical payment reconciliation events exist in the selected scope.'
                : "{$count} unresolved critical reconciliation event(s) remain.",
            ['count' => $count]
        );
    }

    private function checkScopeSnapshot(array &$checks, array $scope): ?array
    {
        $businessId = (int) ($scope['business_id'] ?? 0);
        $operatorId = (int) ($scope['pump_operator_id'] ?? 0);
        $shiftId = (int) ($scope['shift_id'] ?? 0);

        if ($businessId <= 0 && $operatorId <= 0 && $shiftId <= 0) {
            $this->add($checks, 'exact_scope_snapshot', 'WARN', 'No exact business/operator/Shift scope supplied; snapshot acceptance check was skipped.');
            return null;
        }

        if ($businessId <= 0 || $operatorId <= 0 || $shiftId <= 0) {
            $this->add($checks, 'exact_scope_snapshot', 'FAIL', 'Exact snapshot verification requires business_id, pump_operator_id and shift_id together.');
            return null;
        }

        try {
            $snapshot = $this->snapshotService->build(
                $businessId,
                $operatorId,
                [$shiftId],
                ! empty($scope['settlement_id']) ? (int) $scope['settlement_id'] : null,
                ! empty($scope['settlement_no']) ? (string) $scope['settlement_no'] : null,
                true
            );

            $this->add(
                $checks,
                'exact_scope_snapshot',
                empty($snapshot['blocking_issues']) ? 'PASS' : 'FAIL',
                empty($snapshot['blocking_issues'])
                    ? 'The exact Shift snapshot reconciles and can proceed to finalization.'
                    : count($snapshot['blocking_issues']) . ' blocking snapshot issue(s) were found.',
                [
                    'fingerprint' => $snapshot['fingerprint'] ?? null,
                    'payment_ids' => $snapshot['payment_ids'] ?? [],
                    'totals' => $snapshot['totals'] ?? [],
                    'blocking_issues' => $snapshot['blocking_issues'] ?? [],
                ]
            );

            return $snapshot;
        } catch (Throwable $e) {
            $this->add($checks, 'exact_scope_snapshot', 'FAIL', 'Snapshot verification failed: ' . $e->getMessage());
            return null;
        }
    }

    private function applyMasterScope($query, array $scope): void
    {
        if (! empty($scope['business_id'])) {
            $query->where('business_id', (int) $scope['business_id']);
        }
        if (! empty($scope['pump_operator_id'])) {
            $query->where('pump_operator_id', (int) $scope['pump_operator_id']);
        }
        if (! empty($scope['shift_id'])) {
            $query->where('shift_id', (int) $scope['shift_id']);
        }
    }

    private function applyDetailScope(
        $query,
        array $scope,
        string $table,
        string $alias,
        string $operatorColumn
    ): void {
        if (! empty($scope['business_id'])) {
            $query->where("{$alias}.business_id", (int) $scope['business_id']);
        }
        if (! empty($scope['pump_operator_id']) && Schema::hasColumn($table, $operatorColumn)) {
            $query->where("{$alias}.{$operatorColumn}", (int) $scope['pump_operator_id']);
        }
        if (! empty($scope['shift_id'])) {
            $query->where("{$alias}.shift_id", (int) $scope['shift_id']);
        }
    }

    private function triggerShortName(string $table): string
    {
        return match ($table) {
            'settlement_cash_payments' => 'scp',
            'settlement_card_payments' => 'scardp',
            'settlement_cheque_payments' => 'schqp',
            'settlement_credit_sale_payments' => 'scrp',
            'settlement_shortage_payments' => 'sshp',
            'settlement_excess_payments' => 'sexp',
            'daily_collections' => 'dcol',
            'daily_cards' => 'dcard',
            'daily_cheque_payments' => 'dchq',
            'daily_vouchers' => 'dvch',
            default => substr(preg_replace('/[^a-z0-9]/i', '', $table), 0, 20),
        };
    }

    private function add(array &$checks, string $key, string $status, string $message, array $context = []): void
    {
        $checks[] = [
            'key' => $key,
            'status' => $status,
            'message' => $message,
            'context' => $context,
        ];
    }
}
