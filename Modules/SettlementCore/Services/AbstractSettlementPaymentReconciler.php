<?php

namespace Modules\SettlementCore\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * MA-002: shared settlement payment reconciler engine.
 *
 * WHY THIS EXISTS
 * ---------------
 * Vat previously delegated its settlement payment writes to
 * Modules\Petro\Services\SettlementPaymentReconciler, and Petro in turn
 * imported three Vat models so it could write them. The two modules were
 * coupled in BOTH directions.
 *
 * Duplicating the reconciler into Vat was not an option: the guard trait
 * exists precisely to ensure settlement payments have EXACTLY ONE writer,
 * and a second copy would defeat it. So the engine moved here instead,
 * and each module supplies only its own table -> model map.
 *
 * WHAT IS SHARED AND WHAT IS NOT
 * ------------------------------
 * Shared (this class):
 *   - the upsert / reconcile / wipe algorithms, unchanged
 *   - the write-guard flag lifecycle
 * Per module (the subclass):
 *   - tableToModel()          which tables it owns
 *   - tableDefaultIdentity()  the identity column per table
 *
 * THE GUARD FLAG
 * --------------
 * Every write path binds BOTH:
 *   - the shared flag  settlementcore.reconciler.active
 *   - the subclass's own legacy flag, e.g. petro.reconciler.active
 *
 * Binding both is deliberate. Existing entities that still use a
 * module-local RequiresReconcilerContext keep working untouched, while
 * entities moved onto the shared trait are satisfied by the shared flag.
 * That makes the migration incremental instead of a flag-day change.
 *
 * Flags are always released in a finally block so they cannot leak on
 * exception.
 */
abstract class AbstractSettlementPaymentReconciler
{
    protected const ALLOWED_IDENTITY_KEYS = [
        'pump_payment_id',
        'customer_payment_id',
        'transaction_id',
    ];

    /** Canonical flag checked by the shared RequiresReconcilerContext trait. */
    public const SHARED_FLAG = 'settlementcore.reconciler.active';

    /**
     * Tables this reconciler owns, mapped to their Eloquent model class.
     *
     * @return array<string, class-string<Model>>
     */
    abstract protected function tableToModel(): array;

    /**
     * Default identity column per table.
     *
     * @return array<string, string>
     */
    abstract protected function tableDefaultIdentity(): array;

    /**
     * The module's own historical flag, kept so entities that still use a
     * module-local guard trait continue to work during migration.
     */
    abstract protected function legacyFlag(): ?string;

    /**
     * Every flag this reconciler binds while writing.
     *
     * @return array<int, string>
     */
    protected function contextFlags(): array
    {
        $flags = [self::SHARED_FLAG];

        $legacy = $this->legacyFlag();
        if (! empty($legacy)) {
            $flags[] = $legacy;
        }

        return $flags;
    }

    protected function bindContext(): void
    {
        foreach ($this->contextFlags() as $flag) {
            app()->instance($flag, true);
        }
    }

    protected function releaseContext(): void
    {
        foreach ($this->contextFlags() as $flag) {
            app()->forgetInstance($flag);
        }
    }

    /**
     * Incremental upsert by (business_id, settlement_no, $identityKey).
     * Never deletes rows. Returns the resulting Eloquent model.
     *
     * @param string|null $settlementNo NULL accepted - some credit-sale writes
     *                                  happen before the shift is settled.
     */
    public function upsertOne(
        int $businessId,
        ?string $settlementNo,
        string $table,
        array $row,
        ?string $identityKey = null
    ): Model {
        $this->assertTable($table);

        $identityKey = $identityKey ?? $this->tableDefaultIdentity()[$table];
        if (! in_array($identityKey, static::ALLOWED_IDENTITY_KEYS, true)) {
            throw new \InvalidArgumentException("Unsupported identity key: {$identityKey}");
        }

        $modelClass = $this->tableToModel()[$table];

        return DB::transaction(function () use ($businessId, $settlementNo, $row, $modelClass, $identityKey) {
            $this->bindContext();
            try {
                $identityValue = $row[$identityKey] ?? null;

                $payload = array_merge($row, [
                    'business_id'   => $businessId,
                    'settlement_no' => $settlementNo,
                ]);

                // Orphan path - manual Add Payment rows may have no source identity.
                if ($identityValue === null) {
                    return $modelClass::create($payload);
                }

                $query = $modelClass::where('business_id', $businessId)
                    ->where($identityKey, $identityValue);

                if ($settlementNo === null) {
                    $query->whereNull('settlement_no');
                } else {
                    $query->where('settlement_no', $settlementNo);
                }

                $existing = $query->first();

                if ($existing) {
                    $existing->fill($payload);
                    $existing->save();

                    return $existing;
                }

                return $modelClass::create($payload);
            } finally {
                $this->releaseContext();
            }
        });
    }

    /**
     * Full-set replacement. Inserts, updates AND deletes to converge $table
     * for ($businessId, $settlementNo) to exactly $desiredRows.
     *
     * RESERVED - do not call per-insert from a loop, it would wipe siblings.
     *
     * @return array{inserted:int,updated:int,deleted:int,orphans:int}
     */
    public function reconcileSet(
        int $businessId,
        string $settlementNo,
        string $table,
        Collection $desiredRows,
        ?string $identityKey = null
    ): array {
        $this->assertTable($table);

        $identityKey = $identityKey ?? $this->tableDefaultIdentity()[$table];
        if (! in_array($identityKey, static::ALLOWED_IDENTITY_KEYS, true)) {
            throw new \InvalidArgumentException("Unsupported identity key: {$identityKey}");
        }

        return DB::transaction(function () use ($businessId, $settlementNo, $table, $desiredRows, $identityKey) {
            $this->bindContext();
            try {
                $existing = DB::table($table)
                    ->where('business_id', $businessId)
                    ->where('settlement_no', $settlementNo)
                    ->whereNotNull($identityKey)
                    ->get()
                    ->keyBy($identityKey);

                $desired = $desiredRows
                    ->filter(fn ($r) => ! is_null($r[$identityKey] ?? null))
                    ->keyBy(fn ($r) => $r[$identityKey]);

                $orphans = $desiredRows->filter(fn ($r) => is_null($r[$identityKey] ?? null));

                $toInsert = $desired->diffKeys($existing);
                $toUpdate = $desired->intersectByKeys($existing);
                $toDelete = $existing->diffKeys($desired);

                foreach ($toInsert as $row) {
                    DB::table($table)->insert(array_merge((array) $row, [
                        'business_id'   => $businessId,
                        'settlement_no' => $settlementNo,
                    ]));
                }

                foreach ($toUpdate as $identityValue => $row) {
                    DB::table($table)
                        ->where('business_id', $businessId)
                        ->where('settlement_no', $settlementNo)
                        ->where($identityKey, $identityValue)
                        ->update((array) $row);
                }

                if ($toDelete->isNotEmpty()) {
                    DB::table($table)
                        ->where('business_id', $businessId)
                        ->where('settlement_no', $settlementNo)
                        ->whereIn($identityKey, $toDelete->keys()->all())
                        ->delete();
                }

                foreach ($orphans as $row) {
                    DB::table($table)->insert(array_merge((array) $row, [
                        'business_id'   => $businessId,
                        'settlement_no' => $settlementNo,
                    ]));
                }

                return [
                    'inserted' => $toInsert->count(),
                    'updated'  => $toUpdate->count(),
                    'deleted'  => $toDelete->count(),
                    'orphans'  => $orphans->count(),
                ];
            } finally {
                $this->releaseContext();
            }
        });
    }

    /**
     * Destroy-path primitive. Deletes every row in $table for the given
     * (business_id, settlement_no), including rows with NULL identity.
     */
    public function wipeAllForSettlement(int $businessId, string $settlementNo, string $table): int
    {
        $this->assertTable($table);

        return DB::transaction(function () use ($businessId, $settlementNo, $table) {
            $this->bindContext();
            try {
                return DB::table($table)
                    ->where('business_id', $businessId)
                    ->where('settlement_no', $settlementNo)
                    ->delete();
            } finally {
                $this->releaseContext();
            }
        });
    }

    private function assertTable(string $table): void
    {
        if (! isset($this->tableToModel()[$table])) {
            throw new \InvalidArgumentException(
                static::class . " does not handle table: {$table}"
            );
        }
    }
}
