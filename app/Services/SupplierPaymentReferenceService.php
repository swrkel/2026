<?php

namespace App\Services;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class SupplierPaymentReferenceService
{
    private const PREFIXES = ['SLP', 'APEP', 'LPEP'];
    private const BACKFILL_VERSION = 'supplier-payment-reference-20260911-plugplay-v1';

    /** @var string|null */
    private static $centralDatabase;

    /** @var array<string, bool> */
    private static $backfillAttempted = [];

    public function setCentralDatabase(?string $database): void
    {
        $database = trim((string) $database);
        if ($database !== '' && self::$centralDatabase === null) {
            self::$centralDatabase = $database;
        }
    }

    /**
     * Generate the next permanent supplier-payment reference.
     *
     * Normal payment saves already run in a DB transaction. The business row is
     * locked, so two users cannot receive the same number for the same business.
     */
    public function next(string $prefix, int $businessId, mixed $paidOn = null): string
    {
        $systemPrefix = $this->normalisePrefix($prefix);
        $year = $this->year($paidOn);

        if ($businessId <= 0) {
            throw new \InvalidArgumentException('A valid business is required to generate the supplier payment reference.');
        }

        // Historical conversion is deliberately performed only outside an open
        // payment transaction because its safety backup tables use DDL.
        if (DB::transactionLevel() === 0) {
            $this->tryHistoricalBackfill();
        }

        if (DB::transactionLevel() > 0) {
            return $this->nextInsideTransaction($systemPrefix, $businessId, $year);
        }

        return DB::transaction(
            fn (): string => $this->nextInsideTransaction($systemPrefix, $businessId, $year),
            3
        );
    }

    /** Preview only; POST/save always generates the authoritative value again. */
    public function preview(string $prefix, int $businessId, mixed $paidOn = null): string
    {
        $systemPrefix = $this->normalisePrefix($prefix);
        $year = $this->year($paidOn);

        if (DB::transactionLevel() === 0) {
            $this->tryHistoricalBackfill();
        }

        $profile = $this->paymentProfile($systemPrefix, $businessId, false);
        $visiblePrefix = $profile['prefix'];
        $startingNumber = $profile['starting_number'];
        $numberLength = $profile['number_length'];
        $next = $this->nextNumberFloor($systemPrefix, $visiblePrefix, $businessId, $year, $startingNumber) + 1;

        return $this->formatConfigured($visiblePrefix, $year, $next, $numberLength);
    }

    public function isSystemReference(?string $value, ?string $prefix = null): bool
    {
        $value = trim((string) $value);
        if ($value === '') {
            return false;
        }

        $businessId = $this->contextBusinessId();
        if ($businessId > 0 && class_exists(\Modules\Suppliers\Services\SupplierPaymentReferenceProfileService::class)) {
            try {
                $profiles = app(\Modules\Suppliers\Services\SupplierPaymentReferenceProfileService::class);
                if ($profiles->available() && $profiles->matches($value, $prefix, $businessId)) {
                    return true;
                }
            } catch (\Throwable $e) {
                // Fall back to the legacy stable patterns below.
            }
        }

        if ($prefix !== null) {
            $prefix = $this->normalisePrefix($prefix);
            return (bool) preg_match('/^' . preg_quote($prefix, '/') . '\\d{4}-\\d{4,}$/', $value);
        }

        return (bool) preg_match('/^(SLP|APEP|LPEP)\\d{4}-\\d{4,}$/', $value);
    }

    /**
     * Plug-and-play historical conversion for the CURRENT tenant connection.
     * It is safe to call repeatedly; a per-tenant installation marker prevents
     * the work from being repeated after a successful conversion.
     */
    public function ensureHistoricalBackfill(): void
    {
        if (DB::transactionLevel() > 0 || ! $this->isTenantDatabase()) {
            return;
        }

        $dbName = (string) DB::connection()->getDatabaseName();
        if ($dbName === '') {
            return;
        }

        $key = DB::getDefaultConnection() . '|' . $dbName;
        if (isset(self::$backfillAttempted[$key])) {
            return;
        }
        self::$backfillAttempted[$key] = true;

        if ($this->backfillInstalled()) {
            return;
        }

        $lockName = 'supplier_ref_' . substr(sha1($dbName), 0, 32);
        $hasLock = false;

        try {
            $row = DB::selectOne('SELECT GET_LOCK(?, 5) AS acquired', [$lockName]);
            $hasLock = (int) ($row->acquired ?? 0) === 1;
            if (! $hasLock) {
                return;
            }

            // Another request may have completed while this one waited for the lock.
            if ($this->backfillInstalled()) {
                return;
            }

            $this->createSafetyTables();
            $mapping = $this->buildHistoricalMapping();
            $this->applyHistoricalMapping($mapping, $dbName);
        } catch (\Throwable $e) {
            Log::error('Supplier payment reference automatic historical conversion failed.', [
                'database' => $dbName,
                'message' => $e->getMessage(),
            ]);
        } finally {
            if ($hasLock) {
                try {
                    DB::selectOne('SELECT RELEASE_LOCK(?) AS released', [$lockName]);
                } catch (\Throwable $e) {
                    // Never break a user page because advisory-lock cleanup failed.
                }
            }
        }
    }

    private function tryHistoricalBackfill(): void
    {
        try {
            $this->ensureHistoricalBackfill();
        } catch (\Throwable $e) {
            Log::error('Supplier payment reference auto-install check failed.', [
                'message' => $e->getMessage(),
            ]);
        }
    }

    private function nextInsideTransaction(string $systemPrefix, int $businessId, int $year): string
    {
        if (! Schema::hasTable('business') || ! Schema::hasTable('transaction_payments')) {
            throw new \RuntimeException('Required payment tables are not available.');
        }

        $business = DB::table('business')
            ->where('id', $businessId)
            ->lockForUpdate()
            ->first(['id']);

        if (! $business) {
            throw new \RuntimeException('The selected business could not be found while generating the supplier payment reference.');
        }

        $profile = $this->paymentProfile($systemPrefix, $businessId, true);
        $visiblePrefix = $profile['prefix'];
        $startingNumber = $profile['starting_number'];
        $numberLength = $profile['number_length'];

        $next = $this->nextNumberFloor($systemPrefix, $visiblePrefix, $businessId, $year, $startingNumber) + 1;
        $reference = $this->formatConfigured($visiblePrefix, $year, $next, $numberLength);

        if (! empty($profile['id']) && class_exists(\Modules\Suppliers\Services\SupplierPaymentReferenceProfileService::class)) {
            app(\Modules\Suppliers\Services\SupplierPaymentReferenceProfileService::class)->markUsed((int) $profile['id']);
        }

        return $reference;
    }

    /**
     * While a tenant has not yet been historically converted, candidate count is
     * included in the floor for the legacy default series. Custom user prefixes
     * are independent from the one-time historical SLP/APEP/LPEP conversion.
     */
    private function nextNumberFloor(string $systemPrefix, string $visiblePrefix, int $businessId, int $year, int $startingNumber): int
    {
        $max = max($startingNumber - 1, $this->maxExistingNumber($visiblePrefix, $businessId, $year));

        if ($visiblePrefix === $systemPrefix && $startingNumber === 1 && ! $this->backfillInstalled(false)) {
            try {
                $max = max($max, $this->historicalCandidateCount($systemPrefix, $businessId, $year));
            } catch (\Throwable $e) {
                // Existing permanent refs remain authoritative if a legacy count
                // cannot be determined on an unusual older schema.
            }
        }

        return $max;
    }

    private function maxExistingNumber(string $visiblePrefix, int $businessId, int $year): int
    {
        if (! Schema::hasTable('transaction_payments') || ! Schema::hasColumn('transaction_payments', 'payment_ref_no')) {
            return 0;
        }

        $base = $visiblePrefix . $year . '-';
        $query = DB::table('transaction_payments')
            ->where('business_id', $businessId)
            ->where('payment_ref_no', 'like', $base . '%');

        if (Schema::hasColumn('transaction_payments', 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        $row = $query
            ->selectRaw("MAX(CAST(SUBSTRING_INDEX(payment_ref_no, '-', -1) AS UNSIGNED)) AS max_number")
            ->first();

        return max(0, (int) ($row->max_number ?? 0));
    }

    private function historicalCandidateCount(string $prefix, int $businessId, int $year): int
    {
        $mapping = $this->buildHistoricalMapping(false);
        $count = 0;
        foreach ($mapping as $row) {
            if ((int) $row['business_id'] === $businessId
                && (int) $row['ref_year'] === $year
                && (string) $row['source_prefix'] === $prefix) {
                ++$count;
            }
        }

        return $count;
    }

    private function isTenantDatabase(): bool
    {
        $dbName = trim((string) DB::connection()->getDatabaseName());
        if ($dbName === '') {
            return false;
        }

        if (self::$centralDatabase !== null && $dbName === self::$centralDatabase) {
            return false;
        }

        foreach (['transaction_payments', 'transactions', 'contacts', 'business'] as $table) {
            if (! Schema::hasTable($table)) {
                return false;
            }
        }

        return true;
    }

    private function backfillInstalled(bool $createTableCheck = true): bool
    {
        if (! $this->isTenantDatabase()) {
            return true;
        }

        if (! Schema::hasTable('supplier_payment_reference_installations')) {
            return false;
        }

        return DB::table('supplier_payment_reference_installations')
            ->where('version', self::BACKFILL_VERSION)
            ->exists();
    }

    private function createSafetyTables(): void
    {
        DB::statement("CREATE TABLE IF NOT EXISTS supplier_payment_reference_installations (
            version VARCHAR(100) NOT NULL,
            installed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            database_name VARCHAR(191) NULL,
            notes VARCHAR(255) NULL,
            PRIMARY KEY (version)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        DB::statement("CREATE TABLE IF NOT EXISTS supplier_payment_reference_backup_20260911 (
            payment_id BIGINT UNSIGNED NOT NULL,
            old_payment_ref_no VARCHAR(191) NULL,
            captured_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (payment_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        DB::statement("CREATE TABLE IF NOT EXISTS supplier_payment_account_ref_backup_20260911 (
            account_transaction_id BIGINT UNSIGNED NOT NULL,
            old_reff_no VARCHAR(191) NULL,
            captured_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (account_transaction_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        DB::statement("CREATE TABLE IF NOT EXISTS supplier_payment_ledger_ref_backup_20260911 (
            contact_ledger_id BIGINT UNSIGNED NOT NULL,
            old_reff_no VARCHAR(191) NULL,
            captured_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (contact_ledger_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    /** @return array<int, array<string, mixed>> */
    private function buildHistoricalMapping(bool $includeExistingReservations = true): array
    {
        if (! $this->isTenantDatabase()) {
            return [];
        }

        $candidates = [];

        // SLP: one parent supplier due payment, later allocated to one or more purchases.
        $slp = DB::table('transaction_payments as p')
            ->join('contacts as c', 'c.id', '=', 'p.payment_for')
            ->whereNull('p.parent_id')
            ->whereNull('p.transaction_id')
            ->whereIn('c.type', ['supplier', 'both'])
            ->where('p.paid_in_type', 'customer_page');

        if (Schema::hasColumn('transaction_payments', 'deleted_at')) {
            $slp->whereNull('p.deleted_at');
        }
        if (Schema::hasColumn('transaction_payments', 'is_return')) {
            $slp->where(function ($q): void {
                $q->whereNull('p.is_return')->orWhere('p.is_return', 0);
            });
        }
        if (Schema::hasColumn('transaction_payments', 'is_advance')) {
            $slp->where(function ($q): void {
                $q->whereNull('p.is_advance')->orWhere('p.is_advance', 0);
            });
        }

        $childHasDeleted = Schema::hasColumn('transaction_payments', 'deleted_at');
        $slp->whereExists(function ($q) use ($childHasDeleted): void {
            $q->selectRaw('1')
                ->from('transaction_payments as ch')
                ->join('transactions as t', 't.id', '=', 'ch.transaction_id')
                ->whereColumn('ch.parent_id', 'p.id')
                ->whereIn('t.type', ['purchase', 'opening_balance']);
            if ($childHasDeleted) {
                $q->whereNull('ch.deleted_at');
            }
        });

        foreach ($slp->get(['p.id', 'p.business_id', 'p.paid_on', 'p.created_at', 'p.payment_ref_no']) as $row) {
            $sort = $this->candidateDate($row->paid_on ?? null, $row->created_at ?? null);
            $candidates[(int) $row->id] = [
                'payment_id' => (int) $row->id,
                'business_id' => (int) $row->business_id,
                'ref_year' => (int) Carbon::parse($sort)->year,
                'source_prefix' => 'SLP',
                'sort_date' => $sort,
                'current_reference' => (string) ($row->payment_ref_no ?? ''),
            ];
        }

        // Purchase-attached supplier payments: classify initial entry payment vs later payment.
        $direct = DB::table('transaction_payments as tp')
            ->join('transactions as t', function ($join): void {
                $join->on('t.id', '=', 'tp.transaction_id')->where('t.type', '=', 'purchase');
            })
            ->join('contacts as c', 'c.id', '=', 't.contact_id')
            ->whereNull('tp.parent_id')
            ->whereIn('c.type', ['supplier', 'both']);

        if (Schema::hasColumn('transaction_payments', 'deleted_at')) {
            $direct->whereNull('tp.deleted_at');
        }
        if (Schema::hasColumn('transaction_payments', 'is_return')) {
            $direct->where(function ($q): void {
                $q->whereNull('tp.is_return')->orWhere('tp.is_return', 0);
            });
        }

        $directRows = $direct->get([
            'tp.id', 'tp.business_id', 'tp.paid_on', 'tp.created_at', 'tp.payment_ref_no',
            't.created_at as transaction_created_at', 't.transaction_date',
        ]);

        $notes = [];
        if (Schema::hasTable('account_transactions') && Schema::hasColumn('account_transactions', 'transaction_payment_id')) {
            $ids = $directRows->pluck('id')->map(fn ($id) => (int) $id)->all();
            foreach (array_chunk($ids, 1000) as $chunk) {
                $q = DB::table('account_transactions')
                    ->whereIn('transaction_payment_id', $chunk)
                    ->whereNotNull('transaction_payment_id');
                if (Schema::hasColumn('account_transactions', 'deleted_at')) {
                    $q->whereNull('deleted_at');
                }
                foreach ($q->get(['transaction_payment_id', 'note']) as $noteRow) {
                    $notes[(int) $noteRow->transaction_payment_id][] = strtolower(trim((string) ($noteRow->note ?? '')));
                }
            }
        }

        foreach ($directRows as $row) {
            $existing = $this->parseSystemReference((string) ($row->payment_ref_no ?? ''));
            if ($existing !== null) {
                $prefix = $existing['prefix'];
                $year = $existing['year'];
            } else {
                $prefix = 'LPEP';
                foreach ($notes[(int) $row->id] ?? [] as $note) {
                    if (str_starts_with($note, 'additional purchase payment')) {
                        $prefix = 'LPEP';
                        break;
                    }
                    if (str_starts_with($note, 'purchase payment - ')) {
                        $prefix = 'APEP';
                        break;
                    }
                }

                if ($prefix === 'LPEP' && empty($notes[(int) $row->id])) {
                    try {
                        if (! empty($row->created_at) && ! empty($row->transaction_created_at)) {
                            $seconds = abs(Carbon::parse($row->created_at)->diffInSeconds(Carbon::parse($row->transaction_created_at), false));
                            if ($seconds <= 10) {
                                $prefix = 'APEP';
                            }
                        }
                    } catch (\Throwable $e) {
                        // Keep LPEP when legacy timestamps are malformed.
                    }
                }

                $sortForYear = $this->candidateDate($row->paid_on ?? null, $row->created_at ?? null, $row->transaction_date ?? null);
                $year = (int) Carbon::parse($sortForYear)->year;
            }

            $sort = $this->candidateDate($row->paid_on ?? null, $row->created_at ?? null, $row->transaction_date ?? null);
            $candidates[(int) $row->id] = [
                'payment_id' => (int) $row->id,
                'business_id' => (int) $row->business_id,
                'ref_year' => $year,
                'source_prefix' => $prefix,
                'sort_date' => $sort,
                'current_reference' => (string) ($row->payment_ref_no ?? ''),
            ];
        }

        if ($candidates === []) {
            return [];
        }

        // Reserve every existing SLP/APEP/LPEP number, including soft-deleted rows.
        $used = [];
        if ($includeExistingReservations) {
            foreach (DB::table('transaction_payments')
                ->whereNotNull('payment_ref_no')
                ->whereRaw("payment_ref_no REGEXP '^(SLP|APEP|LPEP)[0-9]{4}-[0-9]{4,}$'")
                ->get(['business_id', 'payment_ref_no']) as $row) {
                $parsed = $this->parseSystemReference((string) $row->payment_ref_no);
                if ($parsed !== null) {
                    $used[(int) $row->business_id][$parsed['year']][$parsed['prefix']][$parsed['number']] = true;
                }
            }
        }

        $groups = [];
        foreach ($candidates as $candidate) {
            $groups[$candidate['business_id']][$candidate['ref_year']][$candidate['source_prefix']][] = $candidate;
        }

        $mapping = [];
        foreach ($groups as $businessId => $years) {
            foreach ($years as $year => $prefixes) {
                foreach ($prefixes as $prefix => $rows) {
                    usort($rows, function (array $a, array $b): int {
                        $cmp = strcmp((string) $a['sort_date'], (string) $b['sort_date']);
                        return $cmp !== 0 ? $cmp : ((int) $a['payment_id'] <=> (int) $b['payment_id']);
                    });

                    $next = 1;
                    foreach ($rows as $row) {
                        $parsed = $this->parseSystemReference((string) $row['current_reference']);
                        if ($parsed !== null) {
                            $reference = (string) $row['current_reference'];
                            $used[(int) $businessId][(int) $year][(string) $prefix][$parsed['number']] = true;
                        } else {
                            while (! empty($used[(int) $businessId][(int) $year][(string) $prefix][$next])) {
                                ++$next;
                            }
                            $reference = $this->format((string) $prefix, (int) $year, $next);
                            $used[(int) $businessId][(int) $year][(string) $prefix][$next] = true;
                            ++$next;
                        }

                        $mapping[] = [
                            'payment_id' => (int) $row['payment_id'],
                            'business_id' => (int) $businessId,
                            'ref_year' => (int) $year,
                            'source_prefix' => (string) $prefix,
                            'reference_no' => $reference,
                        ];
                    }
                }
            }
        }

        return $mapping;
    }

    /** @param array<int, array<string, mixed>> $mapping */
    private function applyHistoricalMapping(array $mapping, string $dbName): void
    {
        DB::statement('DROP TEMPORARY TABLE IF EXISTS tmp_supplier_payment_reference_map');
        DB::statement("CREATE TEMPORARY TABLE tmp_supplier_payment_reference_map (
            payment_id BIGINT UNSIGNED NOT NULL,
            business_id BIGINT UNSIGNED NOT NULL,
            ref_year SMALLINT UNSIGNED NOT NULL,
            source_prefix VARCHAR(4) NOT NULL,
            reference_no VARCHAR(191) NOT NULL,
            PRIMARY KEY (payment_id),
            KEY idx_supplier_ref_group (business_id, ref_year, source_prefix)
        ) ENGINE=InnoDB");

        foreach (array_chunk($mapping, 500) as $chunk) {
            if ($chunk !== []) {
                DB::table('tmp_supplier_payment_reference_map')->insert($chunk);
            }
        }

        DB::transaction(function () use ($dbName): void {
            DB::statement("INSERT IGNORE INTO supplier_payment_reference_backup_20260911 (payment_id, old_payment_ref_no)
                SELECT tp.id, tp.payment_ref_no
                FROM transaction_payments tp
                INNER JOIN tmp_supplier_payment_reference_map m ON m.payment_id = tp.id");

            $childDeleted = Schema::hasColumn('transaction_payments', 'deleted_at') ? ' AND ch.deleted_at IS NULL' : '';
            DB::statement("INSERT IGNORE INTO supplier_payment_reference_backup_20260911 (payment_id, old_payment_ref_no)
                SELECT ch.id, ch.payment_ref_no
                FROM transaction_payments ch
                INNER JOIN tmp_supplier_payment_reference_map m ON m.source_prefix = 'SLP' AND ch.parent_id = m.payment_id
                WHERE 1=1{$childDeleted}");

            if (Schema::hasTable('account_transactions') && Schema::hasColumn('account_transactions', 'transaction_payment_id') && Schema::hasColumn('account_transactions', 'reff_no')) {
                $deleted = Schema::hasColumn('account_transactions', 'deleted_at') ? ' WHERE atx.deleted_at IS NULL' : '';
                DB::statement("INSERT IGNORE INTO supplier_payment_account_ref_backup_20260911 (account_transaction_id, old_reff_no)
                    SELECT atx.id, atx.reff_no
                    FROM account_transactions atx
                    INNER JOIN tmp_supplier_payment_reference_map m ON m.payment_id = atx.transaction_payment_id{$deleted}");

                $deleted2 = Schema::hasColumn('account_transactions', 'deleted_at') ? ' AND atx.deleted_at IS NULL' : '';
                DB::statement("INSERT IGNORE INTO supplier_payment_account_ref_backup_20260911 (account_transaction_id, old_reff_no)
                    SELECT atx.id, atx.reff_no
                    FROM account_transactions atx
                    INNER JOIN transaction_payments ch ON ch.id = atx.transaction_payment_id
                    INNER JOIN tmp_supplier_payment_reference_map m ON m.source_prefix = 'SLP' AND ch.parent_id = m.payment_id
                    WHERE 1=1{$deleted2}");
            }

            if (Schema::hasTable('contact_ledgers') && Schema::hasColumn('contact_ledgers', 'transaction_payment_id') && Schema::hasColumn('contact_ledgers', 'reff_no')) {
                $deleted = Schema::hasColumn('contact_ledgers', 'deleted_at') ? ' WHERE cl.deleted_at IS NULL' : '';
                DB::statement("INSERT IGNORE INTO supplier_payment_ledger_ref_backup_20260911 (contact_ledger_id, old_reff_no)
                    SELECT cl.id, cl.reff_no
                    FROM contact_ledgers cl
                    INNER JOIN tmp_supplier_payment_reference_map m ON m.payment_id = cl.transaction_payment_id{$deleted}");

                $deleted2 = Schema::hasColumn('contact_ledgers', 'deleted_at') ? ' AND cl.deleted_at IS NULL' : '';
                DB::statement("INSERT IGNORE INTO supplier_payment_ledger_ref_backup_20260911 (contact_ledger_id, old_reff_no)
                    SELECT cl.id, cl.reff_no
                    FROM contact_ledgers cl
                    INNER JOIN transaction_payments ch ON ch.id = cl.transaction_payment_id
                    INNER JOIN tmp_supplier_payment_reference_map m ON m.source_prefix = 'SLP' AND ch.parent_id = m.payment_id
                    WHERE 1=1{$deleted2}");
            }

            DB::statement("UPDATE transaction_payments tp
                INNER JOIN tmp_supplier_payment_reference_map m ON m.payment_id = tp.id
                SET tp.payment_ref_no = m.reference_no");

            DB::statement("UPDATE transaction_payments ch
                INNER JOIN tmp_supplier_payment_reference_map m ON m.source_prefix = 'SLP' AND ch.parent_id = m.payment_id
                SET ch.payment_ref_no = m.reference_no
                WHERE 1=1{$childDeleted}");

            if (Schema::hasTable('account_transactions') && Schema::hasColumn('account_transactions', 'transaction_payment_id') && Schema::hasColumn('account_transactions', 'reff_no')) {
                $deleted = Schema::hasColumn('account_transactions', 'deleted_at') ? ' AND atx.deleted_at IS NULL' : '';
                DB::statement("UPDATE account_transactions atx
                    INNER JOIN tmp_supplier_payment_reference_map m ON m.payment_id = atx.transaction_payment_id
                    SET atx.reff_no = m.reference_no
                    WHERE 1=1{$deleted}");

                DB::statement("UPDATE account_transactions atx
                    INNER JOIN transaction_payments ch ON ch.id = atx.transaction_payment_id
                    INNER JOIN tmp_supplier_payment_reference_map m ON m.source_prefix = 'SLP' AND ch.parent_id = m.payment_id
                    SET atx.reff_no = m.reference_no
                    WHERE 1=1{$deleted}");
            }

            if (Schema::hasTable('contact_ledgers') && Schema::hasColumn('contact_ledgers', 'transaction_payment_id') && Schema::hasColumn('contact_ledgers', 'reff_no')) {
                $deleted = Schema::hasColumn('contact_ledgers', 'deleted_at') ? ' AND cl.deleted_at IS NULL' : '';
                DB::statement("UPDATE contact_ledgers cl
                    INNER JOIN tmp_supplier_payment_reference_map m ON m.payment_id = cl.transaction_payment_id
                    SET cl.reff_no = m.reference_no
                    WHERE 1=1{$deleted}");

                DB::statement("UPDATE contact_ledgers cl
                    INNER JOIN transaction_payments ch ON ch.id = cl.transaction_payment_id
                    INNER JOIN tmp_supplier_payment_reference_map m ON m.source_prefix = 'SLP' AND ch.parent_id = m.payment_id
                    SET cl.reff_no = m.reference_no
                    WHERE 1=1{$deleted}");
            }

            DB::table('supplier_payment_reference_installations')->updateOrInsert(
                ['version' => self::BACKFILL_VERSION],
                [
                    'installed_at' => now(),
                    'database_name' => $dbName,
                    'notes' => 'Automatic plug-and-play supplier payment reference conversion',
                ]
            );
        }, 3);

        DB::statement('DROP TEMPORARY TABLE IF EXISTS tmp_supplier_payment_reference_map');
    }

    private function candidateDate(mixed ...$values): string
    {
        foreach ($values as $value) {
            if ($value === null || trim((string) $value) === '') {
                continue;
            }
            try {
                return Carbon::parse($value)->format('Y-m-d H:i:s');
            } catch (\Throwable $e) {
                // Try the next legacy date source.
            }
        }

        return Carbon::now()->format('Y-m-d H:i:s');
    }

    /** @return array{prefix:string,year:int,number:int}|null */
    private function parseSystemReference(?string $reference): ?array
    {
        $reference = trim((string) $reference);
        if (! preg_match('/^(SLP|APEP|LPEP)(\\d{4})-(\\d{4,})$/', $reference, $m)) {
            return null;
        }

        return [
            'prefix' => $m[1],
            'year' => (int) $m[2],
            'number' => (int) $m[3],
        ];
    }

    private function normalisePrefix(string $prefix): string
    {
        $prefix = strtoupper(trim($prefix));
        if (! in_array($prefix, self::PREFIXES, true)) {
            throw new \InvalidArgumentException('Unsupported supplier payment reference prefix: ' . $prefix);
        }

        return $prefix;
    }

    /** @return array{id:int|null,prefix:string,starting_number:int,number_length:int} */
    private function paymentProfile(string $systemPrefix, int $businessId, bool $lock): array
    {
        $fallback = [
            'id' => null,
            'prefix' => $systemPrefix,
            'starting_number' => 1,
            'number_length' => 4,
        ];

        if (! class_exists(\Modules\Suppliers\Services\SupplierPaymentReferenceProfileService::class)) {
            return $fallback;
        }

        try {
            $service = app(\Modules\Suppliers\Services\SupplierPaymentReferenceProfileService::class);
            if (! $service->available()) {
                return $fallback;
            }
            $row = $service->activeForSystemCode($systemPrefix, $businessId, $lock);
            if (! $row) {
                return $fallback;
            }
            return [
                'id' => (int) $row->id,
                'prefix' => (string) $row->prefix,
                'starting_number' => max(1, (int) $row->starting_number),
                'number_length' => max(4, (int) $row->number_length),
            ];
        } catch (\Throwable $e) {
            Log::warning('Supplier payment prefix settings could not be read; using legacy defaults.', [
                'system_prefix' => $systemPrefix,
                'business_id' => $businessId,
                'message' => $e->getMessage(),
            ]);
            return $fallback;
        }
    }

    private function contextBusinessId(): int
    {
        return (int) (
            session('user.business_id')
            ?: session('business.id')
            ?: (auth()->check() ? (auth()->user()->business_id ?? 0) : 0)
        );
    }

    private function year(mixed $paidOn): int
    {
        if ($paidOn instanceof CarbonInterface) {
            return (int) $paidOn->year;
        }

        $value = trim((string) ($paidOn ?? ''));
        if ($value === '') {
            return (int) Carbon::now()->year;
        }

        try {
            return (int) Carbon::parse($value)->year;
        } catch (\Throwable $e) {
            return (int) Carbon::now()->year;
        }
    }

    private function format(string $prefix, int $year, int $number): string
    {
        return sprintf('%s%d-%04d', $prefix, $year, max(1, $number));
    }

    private function formatConfigured(string $prefix, int $year, int $number, int $length): string
    {
        return $prefix . $year . '-' . str_pad((string) max(1, $number), max(4, $length), '0', STR_PAD_LEFT);
    }
}
