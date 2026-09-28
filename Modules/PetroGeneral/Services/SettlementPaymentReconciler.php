<?php

namespace Modules\PetroGeneral\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\PetroGeneral\Entities\SettlementCardPayment;
use Modules\PetroGeneral\Entities\SettlementCashPayment;
use Modules\PetroGeneral\Entities\SettlementChequePayment;
use Modules\PetroGeneral\Entities\SettlementCreditSalePayment;
use Modules\Vat\Entities\VatSettlementCardPayment;
use Modules\Vat\Entities\VatSettlementCashPayment;
use Modules\Vat\Entities\VatSettlementCreditSalePayment;

/**
 * Reconciles writes to the four settlement_*_payments tables against the source
 * pump_operator_payments identity. Replaces the legacy unguarded ::create() pattern
 * that produced duplicate rows on every settlement edit (IS1293, S 237, etc).
 *
 * Two-method API:
 *   - upsertOne(): incremental single-row upsert by (business_id, settlement_no, pump_payment_id).
 *                  Used at every Step 3 per-call-site replacement of ::create(). Never deletes.
 *   - reconcileSet(): full-set replacement (insert/update/delete). RESERVED for future use.
 *                     Do not call from controllers in Step 3 — would wipe sibling rows if
 *                     called per-insert in a loop.
 *
 * Both methods bind the process-scoped flag 'petrogeneral.reconciler.active' so the
 * RequiresReconcilerContext model guard (Lock 2) allows the write through.
 */
class SettlementPaymentReconciler
{
    private const TABLE_TO_MODEL = [
        'settlement_card_payments'        => SettlementCardPayment::class,
        'settlement_cash_payments'        => SettlementCashPayment::class,
        'settlement_cheque_payments'      => SettlementChequePayment::class,
        'settlement_credit_sale_payments' => SettlementCreditSalePayment::class,
        'vat_settlement_card_payments'        => VatSettlementCardPayment::class,
        'vat_settlement_cash_payments'        => VatSettlementCashPayment::class,
        'vat_settlement_credit_sale_payments' => VatSettlementCreditSalePayment::class,
    ];

    private const TABLE_DEFAULT_IDENTITY = [
        'settlement_card_payments'            => 'pump_payment_id',
        'settlement_cash_payments'            => 'pump_payment_id',
        'settlement_cheque_payments'          => 'pump_payment_id',
        'settlement_credit_sale_payments'     => 'pump_payment_id',
        'vat_settlement_card_payments'        => 'customer_payment_id',
        'vat_settlement_cash_payments'        => 'customer_payment_id',
        'vat_settlement_credit_sale_payments' => 'transaction_id',
    ];

    private const ALLOWED_IDENTITY_KEYS = ['pump_payment_id', 'customer_payment_id', 'transaction_id'];

    /**
     * Incremental upsert by (business_id, settlement_no, $identityKey).
     * Used at every Step 3 call-site replacement of ::create().
     * Never deletes rows. Returns the resulting Eloquent model.
     *
     * Identity key choice:
     *   - 'pump_payment_id'      (default) — pumper-payment-sourced writes. The Reconciler
     *                                          assumes the row's pump_payment_id column links
     *                                          back to pump_operator_payments.id.
     *   - 'customer_payment_id'              — Add-Payment / Customer-payment flows where
     *                                          the source row is a customer_payments row.
     *                                          Used at SettlementPDController.php:10776 et al.
     *
     * @param string|null $settlementNo  NULL accepted — some pumper-dashboard credit-sale
     *                                   writes occur before the shift is settled.
     */
    public function upsertOne(
        int $businessId,
        ?string $settlementNo,
        string $table,
        array $row,
        ?string $identityKey = null
    ): Model {
        $this->assertTable($table);
        $identityKey = $identityKey ?? self::TABLE_DEFAULT_IDENTITY[$table];
        if (! in_array($identityKey, self::ALLOWED_IDENTITY_KEYS, true)) {
            throw new \InvalidArgumentException("Unsupported identity key: {$identityKey}");
        }
        $modelClass = self::TABLE_TO_MODEL[$table];

        return DB::transaction(function () use ($businessId, $settlementNo, $table, $row, $modelClass, $identityKey) {
            app()->instance('petrogeneral.reconciler.active', true);
            try {
                $identityValue = $row[$identityKey] ?? null;

                $payload = array_merge($row, [
                    'business_id'   => $businessId,
                    'settlement_no' => $settlementNo,
                ]);

                // Orphan path — manual Add Payment rows may not have a source pump_operator_payments identity.
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
                app()->forgetInstance('petrogeneral.reconciler.active');
            }
        });
    }

    /**
     * Full-set replacement. RESERVED — do not call from controllers in Step 3.
     * Inserts, updates, AND deletes to converge $table for ($businessId, $settlementNo)
     * to exactly the rows in $desiredRows (keyed by pump_payment_id).
     *
     * @return array{inserted:int,updated:int,deleted:int,orphans:int}
     */
    public function reconcileSet(
        int $businessId,
        string $settlementNo,
        string $table,
        Collection $desiredRows,
        ?string $identityKey = null
    ): array
    {
        $this->assertTable($table);
        $identityKey = $identityKey ?? self::TABLE_DEFAULT_IDENTITY[$table];
        if (! in_array($identityKey, self::ALLOWED_IDENTITY_KEYS, true)) {
            throw new \InvalidArgumentException("Unsupported identity key: {$identityKey}");
        }

        return DB::transaction(function () use ($businessId, $settlementNo, $table, $desiredRows, $identityKey) {
            app()->instance('petrogeneral.reconciler.active', true);
            try {
                $existing = DB::table($table)
                    ->where('business_id', $businessId)
                    ->where('settlement_no', $settlementNo)
                    ->whereNotNull($identityKey)
                    ->get()
                    ->keyBy($identityKey);

                $desired = $desiredRows
                    ->filter(fn($r) => ! is_null($r[$identityKey] ?? null))
                    ->keyBy(fn($r) => $r[$identityKey]);

                $orphans = $desiredRows->filter(fn($r) => is_null($r[$identityKey] ?? null));

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
                app()->forgetInstance('petrogeneral.reconciler.active');
            }
        });
    }

    /**
     * Destroy-path primitive. Deletes every row in $table for the given
     * (business_id, settlement_no), including rows with NULL identity columns.
     */
    public function wipeAllForSettlement(int $businessId, string $settlementNo, string $table): int
    {
        $this->assertTable($table);

        return DB::transaction(function () use ($businessId, $settlementNo, $table) {
            app()->instance('petrogeneral.reconciler.active', true);
            try {
                return DB::table($table)
                    ->where('business_id', $businessId)
                    ->where('settlement_no', $settlementNo)
                    ->delete();
            } finally {
                app()->forgetInstance('petrogeneral.reconciler.active');
            }
        });
    }

    /**
     * Bypass helper for seeders, tests, and any path that legitimately must write
     * directly. Releases the flag in a finally so it cannot leak on exception.
     */
    public static function withBypass(callable $cb)
    {
        app()->instance('petrogeneral.reconciler.active', true);
        try {
            return $cb();
        } finally {
            app()->forgetInstance('petrogeneral.reconciler.active');
        }
    }

    private function assertTable(string $table): void
    {
        if (! isset(self::TABLE_TO_MODEL[$table])) {
            throw new \InvalidArgumentException("SettlementPaymentReconciler does not handle table: {$table}");
        }
    }
}
