<?php

namespace Modules\PetroPD\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\PetroPD\Entities\SettlementCardPayment;
use Modules\PetroPD\Entities\SettlementCashPayment;
use Modules\PetroPD\Entities\SettlementChequePayment;
use Modules\PetroPD\Entities\SettlementCreditSalePayment;
use Modules\PetroPD\Entities\SettlementShortagePayment;
use Modules\PetroPD\Entities\SettlementExcessPayment;
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
 * Both methods bind the process-scoped flag 'petro.reconciler.active' so the
 * RequiresReconcilerContext model guard (Lock 2) allows the write through.
 */
class SettlementPaymentReconciler
{
    private const CONTEXT_ACTIVE_KEY = 'petro.reconciler.active';
    private const CONTEXT_DEPTH_KEY = 'petro.reconciler.depth';

    private const TABLE_TO_MODEL = [
        'settlement_card_payments'        => SettlementCardPayment::class,
        'settlement_cash_payments'        => SettlementCashPayment::class,
        'settlement_cheque_payments'      => SettlementChequePayment::class,
        'settlement_credit_sale_payments' => SettlementCreditSalePayment::class,
        'settlement_shortage_payments'    => SettlementShortagePayment::class,
        'settlement_excess_payments'      => SettlementExcessPayment::class,
        'vat_settlement_card_payments'        => VatSettlementCardPayment::class,
        'vat_settlement_cash_payments'        => VatSettlementCashPayment::class,
        'vat_settlement_credit_sale_payments' => VatSettlementCreditSalePayment::class,
    ];

    private const TABLE_DEFAULT_IDENTITY = [
        'settlement_card_payments'            => 'pump_payment_id',
        'settlement_cash_payments'            => 'pump_payment_id',
        'settlement_cheque_payments'          => 'pump_payment_id',
        'settlement_credit_sale_payments'     => 'pump_payment_id',
        'settlement_shortage_payments'        => 'pump_payment_id',
        'settlement_excess_payments'          => 'pump_payment_id',
        'vat_settlement_card_payments'        => 'customer_payment_id',
        'vat_settlement_cash_payments'        => 'customer_payment_id',
        'vat_settlement_credit_sale_payments' => 'transaction_id',
    ];

    private const ALLOWED_IDENTITY_KEYS = ['pump_payment_id', 'customer_payment_id', 'transaction_id'];

    /**
     * Incremental upsert by the immutable source identity. For pump_payment_id,
     * the identity is (business_id, pump_payment_id); settlement_no is lifecycle metadata,
     * not part of the financial identity.
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
        $this->assertIdentityColumn($table, $identityKey);
        $modelClass = self::TABLE_TO_MODEL[$table];

        return DB::transaction(function () use ($businessId, $settlementNo, $table, $row, $modelClass, $identityKey) {
            self::enterContext();
            try {
                $identityValue = $row[$identityKey] ?? null;

                $payload = array_merge($row, [
                    'business_id'   => $businessId,
                    'settlement_no' => $settlementNo,
                ]);

                // The master Pump Operator Payment owns business/operator/Shift scope.
                // Detail tables may never infer or replace these values from a latest shift.
                if ($identityKey === 'pump_payment_id' && $identityValue !== null) {
                    $master = DB::table('pump_operator_payments')
                        ->where('id', $identityValue)
                        ->lockForUpdate()
                        ->first(['id', 'business_id', 'pump_operator_id', 'shift_id']);

                    if (! $master) {
                        throw new \RuntimeException('Invalid Pump Operator Payment identity.');
                    }

                    if ((int) $master->business_id !== $businessId) {
                        throw new \RuntimeException('Pump Operator Payment belongs to a different business.');
                    }

                    if (empty($master->shift_id)) {
                        throw new \RuntimeException('Pump Operator Payment has no immutable Shift ID.');
                    }

                    if (Schema::hasColumn($table, 'shift_id')) {
                        $payload['shift_id'] = (int) $master->shift_id;
                    }
                    if (Schema::hasColumn($table, 'pump_operator_id')) {
                        $payload['pump_operator_id'] = (int) $master->pump_operator_id;
                    }
                }

                // Orphan path — manual Add Payment rows may not have a source pump_operator_payments identity.
                if ($identityValue === null) {
                    return $modelClass::create($payload);
                }

                $query = $modelClass::where('business_id', $businessId)
                    ->where($identityKey, $identityValue);

                // A Pump Operator Payment ID is globally unique inside a business.
                // settlement_no starts as NULL and is attached later; including it
                // in the lookup creates a second credit header for only some sales.
                if ($identityKey !== 'pump_payment_id') {
                    if ($settlementNo === null) {
                        $query->whereNull('settlement_no');
                    } else {
                        $query->where('settlement_no', $settlementNo);
                    }
                }

                $existingCandidates = $query->lockForUpdate()->limit(2)->get();
                if ($existingCandidates->count() > 1) {
                    throw new \RuntimeException(
                        sprintf(
                            'Duplicate %s records already exist for %s=%s. Reconciliation stopped.',
                            $table,
                            $identityKey,
                            $identityValue
                        )
                    );
                }

                $existing = $existingCandidates->first();
                if ($existing) {
                    if ($identityKey === 'pump_payment_id') {
                        $existingSettlementNo = $existing->settlement_no;
                        $hasExistingSettlement = $existingSettlementNo !== null && $existingSettlementNo !== '';
                        $hasIncomingSettlement = $settlementNo !== null && $settlementNo !== '';

                        if ($hasExistingSettlement
                            && $hasIncomingSettlement
                            && (string) $existingSettlementNo !== (string) $settlementNo) {
                            if (! $this->settlementTokensReferToSameSettlement(
                                $businessId,
                                $existingSettlementNo,
                                $settlementNo
                            )) {
                                throw new \RuntimeException(
                                    sprintf(
                                        'The Pump Operator Payment is already linked to a different settlement. '
                                        . 'Existing: %s; incoming: %s.',
                                        (string) $existingSettlementNo,
                                        (string) $settlementNo
                                    )
                                );
                            }

                            // The same settlement may be represented by its numeric ID
                            // in older payment rows and by its formatted settlement number
                            // in newer controller paths. Preserve the existing token so the
                            // row does not oscillate between the two representations.
                            $payload['settlement_no'] = $existingSettlementNo;
                        }

                        if (! $hasIncomingSettlement && $hasExistingSettlement) {
                            $payload['settlement_no'] = $existingSettlementNo;
                        }
                    }

                    $existing->fill($payload);
                    $existing->save();
                    return $existing;
                }

                return $modelClass::create($payload);
            } finally {
                self::leaveContext();
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
        $this->assertIdentityColumn($table, $identityKey);

        return DB::transaction(function () use ($businessId, $settlementNo, $table, $desiredRows, $identityKey) {
            self::enterContext();
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
                self::leaveContext();
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
            self::enterContext();
            try {
                return DB::table($table)
                    ->where('business_id', $businessId)
                    ->where('settlement_no', $settlementNo)
                    ->delete();
            } finally {
                self::leaveContext();
            }
        });
    }

    /**
     * Bypass helper for seeders, tests, and any path that legitimately must write
     * directly. Releases the flag in a finally so it cannot leak on exception.
     */
    public static function withBypass(callable $cb)
    {
        self::enterContext();
        try {
            return $cb();
        } finally {
            self::leaveContext();
        }
    }

    /**
     * Fail with an actionable message before Laravel builds a query against a
     * tenant database that has not received the payment-identity repair.
     * Financial rows must not fall back to an amount-based or settlement-based
     * match because that can link the wrong authoritative payment.
     */
    /**
     * Enter a depth-aware reconciler context.
     *
     * A boolean-only flag is unsafe when reconciler calls are nested: the
     * inner finally block can clear the flag while the outer reconciliation is
     * still active. The depth counter prevents that race.
     */
    private static function enterContext(): void
    {
        $depth = app()->bound(self::CONTEXT_DEPTH_KEY)
            ? (int) app(self::CONTEXT_DEPTH_KEY)
            : 0;

        app()->instance(self::CONTEXT_DEPTH_KEY, $depth + 1);
        app()->instance(self::CONTEXT_ACTIVE_KEY, true);
    }

    /**
     * Leave one reconciler context level without clearing an outer context.
     */
    private static function leaveContext(): void
    {
        $depth = app()->bound(self::CONTEXT_DEPTH_KEY)
            ? (int) app(self::CONTEXT_DEPTH_KEY)
            : 0;

        if ($depth <= 1) {
            app()->forgetInstance(self::CONTEXT_DEPTH_KEY);
            app()->forgetInstance(self::CONTEXT_ACTIVE_KEY);
            return;
        }

        app()->instance(self::CONTEXT_DEPTH_KEY, $depth - 1);
        app()->instance(self::CONTEXT_ACTIVE_KEY, true);
    }

    /**
     * Determine whether two stored settlement tokens identify the same
     * authoritative row in the settlements table.
     *
     * Older PetroPD paths store settlements.id while newer paths may pass the
     * formatted settlements.settlement_no. A raw string comparison therefore
     * produces false cross-settlement conflicts for the same settlement.
     *
     * Ambiguous or unresolved tokens are never treated as equivalent. This
     * keeps the duplicate-financial-posting guard intact for genuine conflicts.
     */
    private function settlementTokensReferToSameSettlement(
        int $businessId,
        $existingToken,
        $incomingToken
    ): bool {
        $existingIds = $this->resolveSettlementTokenIds($businessId, $existingToken);
        $incomingIds = $this->resolveSettlementTokenIds($businessId, $incomingToken);

        return count($existingIds) === 1
            && count($incomingIds) === 1
            && $existingIds[0] === $incomingIds[0];
    }

    /**
     * Resolve a settlement token conservatively.
     *
     * A numeric token may be either a settlement ID or a literal settlement
     * number. Both are checked; more than one match is considered ambiguous.
     *
     * @return array<int, int>
     */
    private function resolveSettlementTokenIds(int $businessId, $token): array
    {
        if ($token === null || trim((string) $token) === '') {
            return [];
        }

        $token = trim((string) $token);

        return DB::table('settlements')
            ->where('business_id', $businessId)
            ->where(function ($query) use ($token) {
                $query->where('settlement_no', $token);

                if (ctype_digit($token)) {
                    $query->orWhere('id', (int) $token);
                }
            })
            ->pluck('id')
            ->map(static fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    private function assertIdentityColumn(string $table, string $identityKey): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $identityKey)) {
            throw new \RuntimeException(
                sprintf(
                    /*
                     * MA-002: this message named a file that does not exist.
                     * There is no ..._V2.sql anywhere in the codebase, so
                     * anyone following the instruction had nothing to run.
                     * It now names the script that is actually shipped, with
                     * its path.
                     */
                    'Petro PD tenant database update is incomplete: %s.%s is missing. '
                    . 'Run Modules/PetroPD/Database/sql/'
                    . '2026_08_05_REPAIR_ALL_SETTLEMENT_PAYMENT_IDENTITIES_V2.sql '
                    . 'on the affected tenant database and retry.',
                    $table,
                    $identityKey
                )
            );
        }
    }

    private function assertTable(string $table): void
    {
        if (! isset(self::TABLE_TO_MODEL[$table])) {
            throw new \InvalidArgumentException("SettlementPaymentReconciler does not handle table: {$table}");
        }
    }
}
