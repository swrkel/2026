<?php

namespace Modules\Vat\Services;

use Modules\SettlementCore\Services\AbstractSettlementPaymentReconciler;
use Modules\Vat\Entities\VatSettlementCardPayment;
use Modules\Vat\Entities\VatSettlementCashPayment;
use Modules\Vat\Entities\VatSettlementCreditSalePayment;

/**
 * MA-002: Vat-owned settlement payment reconciler.
 *
 * Vat previously called Modules\Petro\Services\SettlementPaymentReconciler,
 * and Petro's reconciler in turn imported these three Vat models so it could
 * write them. The two modules depended on each other in BOTH directions.
 *
 * The write algorithm now lives once, in
 * Modules\SettlementCore\Services\AbstractSettlementPaymentReconciler. This
 * class only declares which tables Vat owns. Petro does the same for its own
 * tables. Neither module references the other.
 *
 * IMPORTANT - this is NOT a duplicate reconciler. The whole point of the
 * Lock 2 guard is that settlement payments have exactly one writer per table.
 * There is still exactly one writer per table; the tables are simply owned by
 * the module they belong to. The vat_settlement_* tables were only ever
 * written from Vat\Http\Controllers\VatAddPaymentController - verified before
 * this change - so no other caller loses its path.
 */
class VatSettlementPaymentReconciler extends AbstractSettlementPaymentReconciler
{
    /**
     * {@inheritDoc}
     */
    protected function tableToModel(): array
    {
        return [
            'vat_settlement_card_payments'        => VatSettlementCardPayment::class,
            'vat_settlement_cash_payments'        => VatSettlementCashPayment::class,
            'vat_settlement_credit_sale_payments' => VatSettlementCreditSalePayment::class,
        ];
    }

    /**
     * {@inheritDoc}
     */
    protected function tableDefaultIdentity(): array
    {
        return [
            'vat_settlement_card_payments'        => 'customer_payment_id',
            'vat_settlement_cash_payments'        => 'customer_payment_id',
            'vat_settlement_credit_sale_payments' => 'transaction_id',
        ];
    }

    /**
     * Vat never had a flag of its own - its models were guarded by Petro's
     * 'petro.reconciler.active'. They now use the shared flag, so there is
     * no legacy flag to keep alive here.
     */
    protected function legacyFlag(): ?string
    {
        return null;
    }

    /**
     * Bypass helper for seeders, tests, and any path that legitimately must
     * write directly. Releases the flag in a finally so it cannot leak.
     */
    public static function withBypass(callable $cb)
    {
        app()->instance(self::SHARED_FLAG, true);
        try {
            return $cb();
        } finally {
            app()->forgetInstance(self::SHARED_FLAG);
        }
    }
}
