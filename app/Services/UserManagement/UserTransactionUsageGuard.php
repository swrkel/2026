<?php

namespace App\Services\UserManagement;

use App\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Prevents deletion of users that are already referenced by operational data.
 *
 * The ERP contains many standalone modules and tenant databases can have a
 * different set of tables. A hard-coded check is therefore not sufficient.
 * This guard discovers user-reference columns in the active tenant database,
 * limits them to operational/transaction tables, and checks all users for the
 * current business in batched UNION queries.
 */
class UserTransactionUsageGuard
{
    /** @var array<int, array<int, bool>> */
    private array $usedIdsByBusiness = [];

    /** @var array<int, bool> */
    private array $verificationFailedByBusiness = [];

    /** @var array<int, array<int, array{table:string,column:string,has_business_id:bool,foreign_key:bool}>> */
    private array $referencesByBusiness = [];

    private const USER_REFERENCE_COLUMNS = [
        'user_id',
        'created_by',
        'updated_by',
        'added_by',
        'deleted_by',
        'approved_by',
        'approved_user',
        'approved_user_id',
        'verified_by',
        'finalized_by',
        'settled_by',
        'edited_by',
        'operator_id',
        'pump_operator_id',
        'cashier_id',
        'sales_agent_id',
        'commission_agent',
        'res_waiter_id',
        'waiter_id',
        'received_by',
        'submitted_by',
        'assigned_to',
        'assigned_by',
        'closed_by',
        'opened_by',
        'posted_by',
        'processed_by',
        'recorded_by',
        'prepared_by',
        'issued_by',
        'collected_by',
        'delivered_by',
        'requested_by',
        'created_user_id',
        'updated_user_id',
        'teller_user_id',
    ];

    /**
     * Tables that are user administration, configuration, audit-only, or queues
     * must not make a user look like they completed a business transaction.
     */
    private const EXCLUDED_TABLES = [
        'users',
        'roles',
        'permissions',
        'model_has_roles',
        'model_has_permissions',
        'role_has_permissions',
        'user_contact_access',
        'user_settings',
        'user_groups',
        'activity_log',
        'audits',
        'sessions',
        'password_resets',
        'personal_access_tokens',
        'push_notification_tokens',
        'notifications',
        'failed_jobs',
        'jobs',
        'job_batches',
        'cache',
        'cache_locks',
        'migrations',
    ];

    /**
     * High-volume/common tables are checked first so most used users are found
     * before optional module tables are evaluated.
     */
    private const PRIORITY_TABLES = [
        'transactions',
        'transaction_payments',
        'account_transactions',
        'cash_registers',
        'cash_register_transactions',
        'pump_operator_payments',
        'pump_operator_meter_sales',
        'pumper_day_entries',
        'settlements',
        'daily_collections',
        'journals',
        'expenses',
        'bookings',
    ];

    /**
     * Return a UI/server-safe deletion decision.
     *
     * @return array{blocked:bool,in_use:bool,verification_failed:bool,reason:string}
     */
    public function deletionStatus(int $userId, int $businessId): array
    {
        $usedIds = $this->usedUserIdsForBusiness($businessId);
        $inUse = isset($usedIds[$userId]);
        $verificationFailed = $this->verificationFailedByBusiness[$businessId] ?? false;

        if ($inUse) {
            return [
                'blocked' => true,
                'in_use' => true,
                'verification_failed' => false,
                'reason' => 'Delete is disabled because transactions are already linked to this user.',
            ];
        }

        // Fail closed. Deleting a used user is more damaging than temporarily
        // disabling deletion when a tenant schema cannot be inspected safely.
        if ($verificationFailed) {
            return [
                'blocked' => true,
                'in_use' => false,
                'verification_failed' => true,
                'reason' => 'Delete is disabled because transaction usage could not be verified safely.',
            ];
        }

        return [
            'blocked' => false,
            'in_use' => false,
            'verification_failed' => false,
            'reason' => '',
        ];
    }

    /**
     * @return array<int, bool> keyed by user id
     */
    private function usedUserIdsForBusiness(int $businessId): array
    {
        if (array_key_exists($businessId, $this->usedIdsByBusiness)) {
            return $this->usedIdsByBusiness[$businessId];
        }

        $this->verificationFailedByBusiness[$businessId] = false;

        $userIds = User::where('business_id', $businessId)
            ->pluck('id')
            ->map(static fn ($id): int => (int) $id)
            ->filter(static fn (int $id): bool => $id > 0)
            ->values()
            ->all();

        if (empty($userIds)) {
            return $this->usedIdsByBusiness[$businessId] = [];
        }

        try {
            $references = $this->discoverReferences($businessId);
        } catch (Throwable $e) {
            // The fallback still detects common core transactions, but it cannot
            // prove that optional module tables were inspected. Keep fail-closed
            // protection active for any user not found by the fallback checks.
            $this->verificationFailedByBusiness[$businessId] = true;
            Log::warning('User transaction usage schema discovery failed.', [
                'business_id' => $businessId,
                'message' => $e->getMessage(),
            ]);
            $references = $this->fallbackReferences();
        }

        if (empty($references)) {
            $this->verificationFailedByBusiness[$businessId] = true;
            return $this->usedIdsByBusiness[$businessId] = [];
        }

        $used = [];
        $successfulBatches = 0;

        // Keep SQL/binding sizes predictable even for tenant databases that have
        // hundreds of optional module tables.
        foreach (array_chunk($references, 24) as $referenceBatch) {
            try {
                [$sql, $bindings] = $this->buildUsageUnion($referenceBatch, $businessId);
                if ($sql === '') {
                    continue;
                }

                $rows = DB::select($sql, $bindings);
                $successfulBatches++;

                foreach ($rows as $row) {
                    $id = (int) ($row->user_id ?? 0);
                    if ($id > 0) {
                        $used[$id] = true;
                    }
                }

                if (count($used) >= count($userIds)) {
                    break;
                }
            } catch (Throwable $e) {
                $this->verificationFailedByBusiness[$businessId] = true;
                Log::warning('A user transaction usage batch could not be checked.', [
                    'business_id' => $businessId,
                    'message' => $e->getMessage(),
                    'tables' => array_values(array_unique(array_column($referenceBatch, 'table'))),
                ]);
            }
        }

        if ($successfulBatches === 0) {
            $this->verificationFailedByBusiness[$businessId] = true;
        }

        return $this->usedIdsByBusiness[$businessId] = $used;
    }

    /**
     * @return array<int, array{table:string,column:string,has_business_id:bool,foreign_key:bool}>
     */
    private function discoverReferences(int $businessId): array
    {
        if (isset($this->referencesByBusiness[$businessId])) {
            return $this->referencesByBusiness[$businessId];
        }

        $columnNames = array_merge(self::USER_REFERENCE_COLUMNS, ['business_id']);
        $placeholders = implode(',', array_fill(0, count($columnNames), '?'));

        $columnRows = DB::select(
            'SELECT TABLE_NAME AS table_name, COLUMN_NAME AS column_name
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND COLUMN_NAME IN (' . $placeholders . ')',
            $columnNames
        );

        $foreignKeyRows = DB::select(
            "SELECT TABLE_NAME AS table_name, COLUMN_NAME AS column_name
             FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = DATABASE()
               AND REFERENCED_TABLE_NAME = 'users'
               AND REFERENCED_COLUMN_NAME = 'id'"
        );

        $foreignKeys = [];
        $foreignKeyColumns = [];
        foreach ($foreignKeyRows as $row) {
            $table = (string) $row->table_name;
            $column = (string) $row->column_name;
            if (!$this->isSafeIdentifier($table) || !$this->isSafeIdentifier($column)) {
                continue;
            }

            $key = strtolower($table) . '|' . strtolower($column);
            $foreignKeys[$key] = true;
            $foreignKeyColumns[$table][strtolower($column)] = $column;
        }

        $tableColumns = [];
        foreach ($columnRows as $row) {
            $table = (string) $row->table_name;
            $column = (string) $row->column_name;
            if (!$this->isSafeIdentifier($table) || !$this->isSafeIdentifier($column)) {
                continue;
            }
            $tableColumns[$table][strtolower($column)] = $column;
        }

        // Include any schema-declared foreign key to users.id, even when an
        // optional module uses a non-standard column name such as author_id.
        foreach ($foreignKeyColumns as $table => $columns) {
            foreach ($columns as $normalizedColumn => $column) {
                $tableColumns[$table][$normalizedColumn] = $column;
            }
        }

        $references = [];
        foreach ($tableColumns as $table => $columns) {
            $hasDeclaredUserForeignKey = ! empty($foreignKeyColumns[$table]);

            // User-administration/configuration/audit records are not business
            // transactions and must not disable deletion by themselves.
            if ($this->isAdministrativeTable($table)) {
                continue;
            }

            // Explicit foreign keys to users.id are authoritative even when a
            // standalone module uses a non-standard table name. Name-pattern
            // detection remains the fallback for legacy tables without FKs.
            if (!$this->isOperationalTable($table) && !$hasDeclaredUserForeignKey) {
                continue;
            }

            $hasBusinessId = isset($columns['business_id']);
            $addedColumns = [];
            foreach (self::USER_REFERENCE_COLUMNS as $candidateColumn) {
                if (!isset($columns[$candidateColumn])) {
                    continue;
                }

                $column = $columns[$candidateColumn];
                $foreignKey = isset($foreignKeys[strtolower($table) . '|' . strtolower($column)]);

                // Ambiguous entity columns are accepted only where the schema
                // confirms users.id or the table name clearly identifies a user role.
                if ($this->isAmbiguousReference($candidateColumn)
                    && !$foreignKey
                    && !$this->tableClearlyUsesCoreUsers($table, $candidateColumn)) {
                    continue;
                }

                $references[] = [
                    'table' => $table,
                    'column' => $column,
                    'has_business_id' => $hasBusinessId,
                    'foreign_key' => $foreignKey,
                ];
                $addedColumns[strtolower($column)] = true;
            }

            foreach ($columns as $normalizedColumn => $column) {
                $foreignKey = isset($foreignKeys[strtolower($table) . '|' . strtolower($column)]);
                if (!$foreignKey || $normalizedColumn === 'business_id' || isset($addedColumns[$normalizedColumn])) {
                    continue;
                }

                $references[] = [
                    'table' => $table,
                    'column' => $column,
                    'has_business_id' => $hasBusinessId,
                    'foreign_key' => true,
                ];
            }
        }

        usort($references, function (array $a, array $b): int {
            $priorityA = $this->referencePriority($a);
            $priorityB = $this->referencePriority($b);
            if ($priorityA === $priorityB) {
                return [$a['table'], $a['column']] <=> [$b['table'], $b['column']];
            }
            return $priorityA <=> $priorityB;
        });

        return $this->referencesByBusiness[$businessId] = $references;
    }

    /**
     * @return array<int, array{table:string,column:string,has_business_id:bool,foreign_key:bool}>
     */
    private function fallbackReferences(): array
    {
        $candidates = [
            ['transactions', 'created_by'],
            ['transactions', 'pump_operator_id'],
            ['transactions', 'approved_user'],
            ['transaction_payments', 'created_by'],
            ['account_transactions', 'created_by'],
            ['account_transactions', 'updated_by'],
            ['cash_registers', 'user_id'],
            ['cash_register_transactions', 'created_by'],
            ['pump_operator_payments', 'created_by'],
            ['pump_operator_payments', 'edited_by'],
            ['pump_operator_payments', 'pump_operator_id'],
            ['pump_operator_meter_sales', 'pump_operator_id'],
            ['pumper_day_entries', 'created_by'],
            ['pumper_day_entries', 'pump_operator_id'],
            ['settlements', 'created_by'],
            ['settlements', 'pump_operator_id'],
            ['daily_collections', 'created_by'],
            ['daily_collections', 'pump_operator_id'],
            ['journals', 'added_by'],
            ['expenses', 'created_by'],
            ['bookings', 'created_by'],
            ['bookings', 'waiter_id'],
        ];

        $references = [];
        foreach ($candidates as [$table, $column]) {
            try {
                if (!Schema::hasTable($table) || !Schema::hasColumn($table, $column)) {
                    continue;
                }
                $references[] = [
                    'table' => $table,
                    'column' => $column,
                    'has_business_id' => Schema::hasColumn($table, 'business_id'),
                    'foreign_key' => false,
                ];
            } catch (Throwable $e) {
                // Continue through the fallback list; final fail-closed handling is
                // applied when no usable reference can be checked.
            }
        }

        return $references;
    }

    /**
     * @param array<int, array{table:string,column:string,has_business_id:bool,foreign_key:bool}> $references
     * @return array{0:string,1:array<int, int>}
     */
    private function buildUsageUnion(array $references, int $businessId): array
    {
        if (empty($references)) {
            return ['', []];
        }

        $userTable = (new User())->getTable();
        if (!$this->isSafeIdentifier($userTable)) {
            return ['', []];
        }

        $selects = [];
        $bindings = [];

        foreach ($references as $index => $reference) {
            $table = $reference['table'];
            $column = $reference['column'];
            if (!$this->isSafeIdentifier($table) || !$this->isSafeIdentifier($column)) {
                continue;
            }

            $sourceAlias = 'usage_source_' . $index;
            $userAlias = 'usage_user_' . $index;
            $select = 'SELECT DISTINCT `' . $sourceAlias . '`.`' . $column . '` AS user_id'
                . ' FROM `' . $table . '` AS `' . $sourceAlias . '`'
                . ' INNER JOIN `' . $userTable . '` AS `' . $userAlias . '`'
                . ' ON `' . $userAlias . '`.`id` = `' . $sourceAlias . '`.`' . $column . '`'
                . ' AND `' . $userAlias . '`.`business_id` = ?'
                . ' WHERE `' . $sourceAlias . '`.`' . $column . '` IS NOT NULL';
            $bindings[] = $businessId;

            if ($reference['has_business_id']) {
                $select .= ' AND `' . $sourceAlias . '`.`business_id` = ?';
                $bindings[] = $businessId;
            }

            $selects[] = $select;
        }

        if (empty($selects)) {
            return ['', []];
        }

        return [
            'SELECT DISTINCT user_id FROM (' . implode(' UNION ALL ', $selects) . ') AS user_transaction_usage',
            $bindings,
        ];
    }

    private function isOperationalTable(string $table): bool
    {
        $name = strtolower($table);
        if ($this->isAdministrativeTable($name)) {
            return false;
        }

        return (bool) preg_match(
            '/(?:^|_)(transactions?|payments?|settlements?|sales?|purchases?|invoices?|orders?|expenses?|journals?|ledgers?|registers?|shifts?|collections?|receipts?|vouchers?|cheques?|checks?|deposits?|transfers?|adjustments?|returns?|refunds?|loans?|installments?|commissions?|bookings?|dispatch(?:es)?|deliver(?:y|ies)|loadings?|movements?|entries?|payroll|attendance|claims?|disbursements?|withdrawals?|teller|vault|reconciliations?|productions?|issues?|meters?|wastages?|finalizes?|work_orders?|free_issues?)(?:_|$)/',
            $name
        ) || (bool) preg_match('/(?:stock_(?:adjust|transfer|movement|taking|verification)|opening_stock|day_end|close_current_sale|pumper_day)/', $name);
    }

    private function isAdministrativeTable(string $table): bool
    {
        $name = strtolower($table);

        if (in_array($name, self::EXCLUDED_TABLES, true)) {
            return true;
        }

        return (bool) preg_match(
            '/(?:^|_)(settings?|types?|categories?|masters?|statuses?|prefixes?|templates?|configs?|options?|permissions?|tokens?|logs?|histories?|snapshots?)(?:_|$)/',
            $name
        );
    }

    private function isAmbiguousReference(string $column): bool
    {
        return in_array($column, [
            'user_id',
            'operator_id',
            'cashier_id',
            'sales_agent_id',
            'commission_agent',
            'res_waiter_id',
            'waiter_id',
        ], true);
    }

    private function tableClearlyUsesCoreUsers(string $table, string $column): bool
    {
        $name = strtolower($table);

        if ($column === 'operator_id' || $column === 'pump_operator_id') {
            return str_contains($name, 'pump') || str_contains($name, 'operator') || str_contains($name, 'meter');
        }

        if ($column === 'cashier_id') {
            return str_contains($name, 'cash') || str_contains($name, 'sale') || str_contains($name, 'register');
        }

        if ($column === 'sales_agent_id' || $column === 'commission_agent') {
            return str_contains($name, 'sale') || str_contains($name, 'commission') || str_contains($name, 'distribution');
        }

        if ($column === 'res_waiter_id' || $column === 'waiter_id') {
            return str_contains($name, 'booking') || str_contains($name, 'restaurant') || str_contains($name, 'order');
        }

        if ($column === 'user_id') {
            return in_array($name, ['cash_registers', 'cash_register_transactions'], true)
                || str_contains($name, 'teller')
                || str_contains($name, 'transaction')
                || str_contains($name, 'payment')
                || str_contains($name, 'shift');
        }

        return false;
    }

    /** @param array{table:string,column:string,has_business_id:bool,foreign_key:bool} $reference */
    private function referencePriority(array $reference): int
    {
        $tableIndex = array_search(strtolower($reference['table']), self::PRIORITY_TABLES, true);
        if ($tableIndex !== false) {
            return (int) $tableIndex;
        }

        if ($reference['foreign_key']) {
            return 100;
        }

        if (in_array($reference['column'], ['created_by', 'user_id', 'pump_operator_id'], true)) {
            return 200;
        }

        return 300;
    }

    private function isSafeIdentifier(string $identifier): bool
    {
        return (bool) preg_match('/^[A-Za-z0-9_]+$/', $identifier);
    }
}
