<?php

namespace Tests\Feature\Vat;

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Modules\Petro\Services\SettlementPaymentReconciler;
use Modules\Vat\Entities\VatSettlementCashPayment;
use Tests\Feature\Petro\PetroTestCase;

/**
 * @group characterization
 */
class VatSettlementPaymentLockTest extends PetroTestCase
{
    use DatabaseTransactions;

    /** @test */
    public function vat_models_throw_on_direct_create_outside_reconciler(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/restricted to SettlementPaymentReconciler/');

        VatSettlementCashPayment::create([
            'business_id' => $this->businessId,
            'settlement_no' => 'VAT-' . uniqid(),
            'customer_id' => $this->contactId,
            'amount' => 100,
            'customer_payment_id' => 900001,
        ]);
    }

    /** @test */
    public function vat_cash_uses_customer_payment_id_as_default_identity(): void
    {
        $settlementNo = 'VAT-CASH-' . uniqid();
        $customerPaymentId = 910000 + random_int(1, 999);
        $reconciler = app(SettlementPaymentReconciler::class);

        $reconciler->upsertOne($this->businessId, $settlementNo, 'vat_settlement_cash_payments', [
            'customer_id' => $this->contactId,
            'amount' => 100,
            'customer_payment_id' => $customerPaymentId,
        ]);
        $reconciler->upsertOne($this->businessId, $settlementNo, 'vat_settlement_cash_payments', [
            'customer_id' => $this->contactId,
            'amount' => 250,
            'customer_payment_id' => $customerPaymentId,
        ]);

        $rows = DB::table('vat_settlement_cash_payments')
            ->where('settlement_no', $settlementNo)
            ->where('customer_payment_id', $customerPaymentId)
            ->get();

        $this->assertCount(1, $rows);
        $this->assertEqualsWithDelta(250, (float) $rows[0]->amount, 0.001);
    }

    /** @test */
    public function vat_card_uses_customer_payment_id_as_default_identity(): void
    {
        $settlementNo = 'VAT-CARD-' . uniqid();
        $customerPaymentId = 920000 + random_int(1, 999);
        $reconciler = app(SettlementPaymentReconciler::class);

        $reconciler->upsertOne($this->businessId, $settlementNo, 'vat_settlement_card_payments', [
            'customer_id' => $this->contactId,
            'amount' => 100,
            'card_type' => 1,
            'customer_payment_id' => $customerPaymentId,
        ]);
        $reconciler->upsertOne($this->businessId, $settlementNo, 'vat_settlement_card_payments', [
            'customer_id' => $this->contactId,
            'amount' => 275,
            'card_type' => 1,
            'customer_payment_id' => $customerPaymentId,
        ]);

        $rows = DB::table('vat_settlement_card_payments')
            ->where('settlement_no', $settlementNo)
            ->where('customer_payment_id', $customerPaymentId)
            ->get();

        $this->assertCount(1, $rows);
        $this->assertEqualsWithDelta(275, (float) $rows[0]->amount, 0.001);
    }

    /** @test */
    public function vat_credit_sale_uses_transaction_id_as_default_identity(): void
    {
        $settlementNo = 'VAT-CREDIT-' . uniqid();
        $transactionId = 930000 + random_int(1, 999);
        $reconciler = app(SettlementPaymentReconciler::class);

        $row = [
            'customer_id' => $this->contactId,
            'product_id' => $this->productId,
            'order_number' => 'VAT-ORDER-' . uniqid(),
            'order_date' => now()->toDateString(),
            'price' => 100,
            'discount' => 0,
            'total_discount' => 0,
            'sub_total' => 100,
            'qty' => 1,
            'amount' => 100,
            'transaction_id' => $transactionId,
        ];

        $reconciler->upsertOne($this->businessId, $settlementNo, 'vat_settlement_credit_sale_payments', $row);
        $row['amount'] = 300;
        $row['sub_total'] = 300;
        $reconciler->upsertOne($this->businessId, $settlementNo, 'vat_settlement_credit_sale_payments', $row);

        $rows = DB::table('vat_settlement_credit_sale_payments')
            ->where('settlement_no', $settlementNo)
            ->where('transaction_id', $transactionId)
            ->get();

        $this->assertCount(1, $rows);
        $this->assertEqualsWithDelta(300, (float) $rows[0]->amount, 0.001);
    }

    /** @test */
    public function vat_unique_constraint_rejects_duplicate_identity_rows(): void
    {
        $settlementNo = 'VAT-LOCK1-' . uniqid();
        $customerPaymentId = 940000 + random_int(1, 999);
        $row = [
            'business_id' => $this->businessId,
            'settlement_no' => $settlementNo,
            'customer_id' => $this->contactId,
            'amount' => 100,
            'customer_payment_id' => $customerPaymentId,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        DB::table('vat_settlement_cash_payments')->insert($row);

        try {
            DB::table('vat_settlement_cash_payments')->insert($row);
            $this->fail('Expected QueryException due to VAT UNIQUE constraint violation.');
        } catch (QueryException $e) {
            $this->assertEquals('23000', $e->getCode());
        }
    }
}
