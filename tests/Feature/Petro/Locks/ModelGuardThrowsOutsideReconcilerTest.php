<?php

namespace Tests\Feature\Petro\Locks;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Petro\Entities\SettlementCardPayment;
use Modules\Petro\Entities\SettlementCashPayment;
use Modules\Petro\Entities\SettlementChequePayment;
use Modules\Petro\Entities\SettlementCreditSalePayment;
use Tests\Feature\Petro\PetroTestCase;

/**
 * Lock 2 — Eloquent model guard.
 *
 * Direct ::create() outside SettlementPaymentReconciler context throws.
 * `updating` is intentionally NOT guarded today (see RequiresReconcilerContext).
 *
 * @group characterization
 */
class ModelGuardThrowsOutsideReconcilerTest extends PetroTestCase
{
    use DatabaseTransactions;

    /** @test */
    public function settlement_card_payment_create_outside_reconciler_throws(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/restricted to SettlementPaymentReconciler/');

        SettlementCardPayment::create([
            'business_id'   => $this->businessId,
            'settlement_no' => 'TST-' . uniqid(),
            'customer_id'   => $this->contactId,
            'amount'        => 100,
            'card_type'     => 1,
        ]);
    }

    /** @test */
    public function settlement_cash_payment_create_outside_reconciler_throws(): void
    {
        $this->expectException(\RuntimeException::class);
        SettlementCashPayment::create([
            'business_id'   => $this->businessId,
            'settlement_no' => 'TST-' . uniqid(),
            'customer_id'   => $this->contactId,
            'amount'        => 100,
        ]);
    }

    /** @test */
    public function settlement_cheque_payment_create_outside_reconciler_throws(): void
    {
        $this->expectException(\RuntimeException::class);
        SettlementChequePayment::create([
            'business_id'   => $this->businessId,
            'settlement_no' => 'TST-' . uniqid(),
            'customer_id'   => $this->contactId,
            'bank_name'     => 'X',
            'cheque_number' => 'Y',
            'cheque_date'   => now()->toDateString(),
            'amount'        => 100,
        ]);
    }

    /** @test */
    public function settlement_credit_sale_payment_create_outside_reconciler_throws(): void
    {
        $this->expectException(\RuntimeException::class);
        SettlementCreditSalePayment::create($this->buildCreditSalePaymentData());
    }
}
