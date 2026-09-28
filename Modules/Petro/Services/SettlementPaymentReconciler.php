<?php

namespace Modules\Petro\Services;

use Modules\Petro\Entities\SettlementCardPayment;
use Modules\Petro\Entities\SettlementCashPayment;
use Modules\Petro\Entities\SettlementChequePayment;
use Modules\Petro\Entities\SettlementCreditSalePayment;
use Modules\SettlementCore\Services\AbstractSettlementPaymentReconciler;

/**
 * Reconciles writes to the settlement_*_payments tables against the source
 * pump_operator_payments identity. Replaces the legacy unguarded ::create()
 * pattern that produced duplicate rows on every settlement edit (IS1293,
 * S 237, etc).
 *
 * Two-method API, unchanged:
 *   - upsertOne()    incremental single-row upsert by
 *                    (business_id, settlement_no, identity). Never deletes.
 *   - reconcileSet() full-set replacement (insert/update/delete). RESERVED -
 *                    do not call per-insert from a loop, it would wipe
 *                    sibling rows.
 *
 * MA-002 CHANGE
 * -------------
 * The write algorithm moved to
 * Modules\SettlementCore\Services\AbstractSettlementPaymentReconciler. This
 * class now declares only the tables Petro owns. The behaviour of
 * upsertOne(), reconcileSet(), wipeAllForSettlement() and withBypass() is
 * unchanged - the code was moved, not rewritten.
 *
 * The three vat_settlement_* tables were REMOVED from this map. They are now
 * owned by Modules\Vat\Services\VatSettlementPaymentReconciler. Verified
 * before the change: the only caller that ever passed a vat_* table was
 * Vat\Http\Controllers\VatAddPaymentController, and every caller of THIS
 * class passes only settlement_* tables. Petro no longer imports any Vat
 * model, so the two modules no longer depend on each other in either
 * direction.
 *
 * Both the shared flag and the historical 'petro.reconciler.active' flag are
 * bound on every write, so Petro entities still using the module-local
 * RequiresReconcilerContext keep working untouched.
 */
class SettlementPaymentReconciler extends AbstractSettlementPaymentReconciler
{
    /** Historical flag, still honoured by Petro's own guard trait. */
    public const LEGACY_FLAG = 'petro.reconciler.active';

    /**
     * {@inheritDoc}
     */
    protected function tableToModel(): array
    {
        return [
            'settlement_card_payments'        => SettlementCardPayment::class,
            'settlement_cash_payments'        => SettlementCashPayment::class,
            'settlement_cheque_payments'      => SettlementChequePayment::class,
            'settlement_credit_sale_payments' => SettlementCreditSalePayment::class,
        ];
    }

    /**
     * {@inheritDoc}
     *
     * Identity key choice:
     *   - 'pump_payment_id'     (default) pumper-payment-sourced writes; the
     *                           row's pump_payment_id links back to
     *                           pump_operator_payments.id
     *   - 'customer_payment_id' Add-Payment / customer-payment flows
     */
    protected function tableDefaultIdentity(): array
    {
        return [
            'settlement_card_payments'        => 'pump_payment_id',
            'settlement_cash_payments'        => 'pump_payment_id',
            'settlement_cheque_payments'      => 'pump_payment_id',
            'settlement_credit_sale_payments' => 'pump_payment_id',
        ];
    }

    /**
     * {@inheritDoc}
     */
    protected function legacyFlag(): ?string
    {
        return self::LEGACY_FLAG;
    }

    /**
     * Bypass helper for seeders, tests, and any path that legitimately must
     * write directly. Releases both flags in a finally so neither can leak.
     *
     * Still called by Modules\Petro\Database\Seeders\PetroDummyDataSeeder.
     */
    public static function withBypass(callable $cb)
    {
        app()->instance(self::SHARED_FLAG, true);
        app()->instance(self::LEGACY_FLAG, true);
        try {
            return $cb();
        } finally {
            app()->forgetInstance(self::SHARED_FLAG);
            app()->forgetInstance(self::LEGACY_FLAG);
        }
    }
}
