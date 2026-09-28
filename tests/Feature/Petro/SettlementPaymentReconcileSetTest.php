<?php

namespace Tests\Feature\Petro;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Modules\Petro\Services\SettlementPaymentReconciler;

/**
 * RESERVED API: SettlementPaymentReconciler::reconcileSet().
 *
 * Not called from any controller today. These tests lock its contract
 * (insert/update/delete to converge to a desired set) so future callers
 * know exactly what it does. Method intentionally deletes rows that are
 * absent from the desired set — never call this with a single-row
 * collection in a loop.
 *
 * @group reserved-api
 */
class SettlementPaymentReconcileSetTest extends PetroTestCase
{
    use DatabaseTransactions;

    /** @test */
    public function empty_existing_three_desired_inserts_three(): void
    {
        $settlementNo = 'TST-RS-' . uniqid();
        $pops = [
            $this->seedPumpOperatorPayment(['payment_type' => 'cash']),
            $this->seedPumpOperatorPayment(['payment_type' => 'cash']),
            $this->seedPumpOperatorPayment(['payment_type' => 'cash']),
        ];

        $desired = collect([
            ['customer_id' => $this->contactId, 'amount' => 100, 'pump_payment_id' => $pops[0]],
            ['customer_id' => $this->contactId, 'amount' => 200, 'pump_payment_id' => $pops[1]],
            ['customer_id' => $this->contactId, 'amount' => 300, 'pump_payment_id' => $pops[2]],
        ]);

        $result = app(SettlementPaymentReconciler::class)
            ->reconcileSet($this->businessId, $settlementNo, 'settlement_cash_payments', $desired);

        $this->assertEquals(3, $result['inserted']);
        $this->assertEquals(0, $result['updated']);
        $this->assertEquals(0, $result['deleted']);
    }

    /** @test */
    public function existing_rows_not_in_desired_set_ARE_deleted(): void
    {
        $settlementNo = 'TST-RS-' . uniqid();
        $popKeep   = $this->seedPumpOperatorPayment(['payment_type' => 'cash']);
        $popDelete = $this->seedPumpOperatorPayment(['payment_type' => 'cash']);

        $reconciler = app(SettlementPaymentReconciler::class);
        $reconciler->upsertOne($this->businessId, $settlementNo, 'settlement_cash_payments', [
            'customer_id' => $this->contactId, 'amount' => 100, 'pump_payment_id' => $popKeep,
        ]);
        $reconciler->upsertOne($this->businessId, $settlementNo, 'settlement_cash_payments', [
            'customer_id' => $this->contactId, 'amount' => 200, 'pump_payment_id' => $popDelete,
        ]);

        $desired = collect([
            ['customer_id' => $this->contactId, 'amount' => 100, 'pump_payment_id' => $popKeep],
        ]);

        $result = $reconciler->reconcileSet($this->businessId, $settlementNo, 'settlement_cash_payments', $desired);

        $this->assertEquals(0, $result['inserted']);
        $this->assertEquals(1, $result['updated']);
        $this->assertEquals(1, $result['deleted'],
            'reconcileSet must DELETE rows absent from desired — this is its defining (dangerous) behaviour.');
        $this->assertEquals(1,
            DB::table('settlement_cash_payments')->where('settlement_no', $settlementNo)->count());
    }

    /** @test */
    public function orphan_rows_with_null_pump_payment_id_are_inserted_separately(): void
    {
        $settlementNo = 'TST-RS-' . uniqid();
        $desired = collect([
            ['customer_id' => $this->contactId, 'amount' => 50, 'pump_payment_id' => null],
            ['customer_id' => $this->contactId, 'amount' => 60, 'pump_payment_id' => null],
        ]);

        $result = app(SettlementPaymentReconciler::class)
            ->reconcileSet($this->businessId, $settlementNo, 'settlement_cash_payments', $desired);

        $this->assertEquals(2, $result['orphans']);
        $this->assertEquals(2,
            DB::table('settlement_cash_payments')->where('settlement_no', $settlementNo)->count());
    }

    /** @test */
    public function vat_credit_sale_reconcile_set_uses_transaction_id_default_identity(): void
    {
        $settlementNo = 'TST-VAT-RS-' . uniqid();
        $existingTx = DB::table('transactions')->insertGetId($this->transactionRow(['final_total' => 10]));
        $keepTx = DB::table('transactions')->insertGetId($this->transactionRow(['final_total' => 20]));

        $reconciler = app(SettlementPaymentReconciler::class);
        $reconciler->upsertOne($this->businessId, $settlementNo, 'vat_settlement_credit_sale_payments', [
            'customer_id' => $this->contactId,
            'product_id' => $this->productId,
            'transaction_id' => $existingTx,
            'amount' => 10,
        ]);

        $desired = collect([
            ['customer_id' => $this->contactId, 'product_id' => $this->productId, 'transaction_id' => $keepTx, 'amount' => 30],
        ]);

        $result = $reconciler->reconcileSet($this->businessId, $settlementNo, 'vat_settlement_credit_sale_payments', $desired);

        $this->assertSame(1, $result['inserted']);
        $this->assertSame(1, $result['deleted']);
        $this->assertEquals(1, DB::table('vat_settlement_credit_sale_payments')->where('settlement_no', $settlementNo)->count());
        $this->assertEquals($keepTx, (int) DB::table('vat_settlement_credit_sale_payments')->where('settlement_no', $settlementNo)->value('transaction_id'));
    }

    /** @test */
    public function vat_cash_reconcile_set_uses_customer_payment_id_default_identity(): void
    {
        $settlementNo = 'TST-VAT-RS-' . uniqid();
        $existingCustomerPaymentId = 910001;
        $keepCustomerPaymentId = 910002;

        $reconciler = app(SettlementPaymentReconciler::class);
        $reconciler->upsertOne($this->businessId, $settlementNo, 'vat_settlement_cash_payments', [
            'customer_id' => $this->contactId,
            'customer_payment_id' => $existingCustomerPaymentId,
            'amount' => 10,
        ]);

        $desired = collect([
            ['customer_id' => $this->contactId, 'customer_payment_id' => $keepCustomerPaymentId, 'amount' => 30],
        ]);

        $result = $reconciler->reconcileSet($this->businessId, $settlementNo, 'vat_settlement_cash_payments', $desired);

        $this->assertSame(1, $result['inserted']);
        $this->assertSame(1, $result['deleted']);
        $this->assertEquals(1, DB::table('vat_settlement_cash_payments')->where('settlement_no', $settlementNo)->count());
        $this->assertEquals($keepCustomerPaymentId, (int) DB::table('vat_settlement_cash_payments')->where('settlement_no', $settlementNo)->value('customer_payment_id'));
    }

    /** @test */
    public function wipe_all_for_settlement_deletes_orphan_and_linked_rows_for_one_settlement_only(): void
    {
        $settlementNo = 'TST-WIPE-' . uniqid();
        $otherSettlementNo = 'TST-WIPE-' . uniqid();
        $pop = $this->seedPumpOperatorPayment(['payment_type' => 'cash']);

        app(SettlementPaymentReconciler::class)->upsertOne($this->businessId, $settlementNo, 'settlement_cash_payments', [
            'customer_id' => $this->contactId,
            'amount' => 10,
            'pump_payment_id' => $pop,
        ]);
        app(SettlementPaymentReconciler::class)->upsertOne($this->businessId, $settlementNo, 'settlement_cash_payments', [
            'customer_id' => $this->contactId,
            'amount' => 11,
            'pump_payment_id' => null,
        ]);
        app(SettlementPaymentReconciler::class)->upsertOne($this->businessId, $otherSettlementNo, 'settlement_cash_payments', [
            'customer_id' => $this->contactId,
            'amount' => 12,
            'pump_payment_id' => null,
        ]);

        $deleted = app(SettlementPaymentReconciler::class)
            ->wipeAllForSettlement($this->businessId, $settlementNo, 'settlement_cash_payments');

        $this->assertSame(2, $deleted);
        $this->assertSame(0, DB::table('settlement_cash_payments')->where('settlement_no', $settlementNo)->count());
        $this->assertSame(1, DB::table('settlement_cash_payments')->where('settlement_no', $otherSettlementNo)->count());
    }

    private function transactionRow(array $overrides = []): array
    {
        return array_merge([
            'business_id' => $this->businessId,
            'location_id' => null,
            'type' => 'sell',
            'status' => 'final',
            'payment_status' => 'paid',
            'invoice_no' => 'TST-RS-' . uniqid(),
            'ref_no' => 'TST-RS-' . uniqid(),
            'transaction_date' => now(),
            'total_before_tax' => 10,
            'final_total' => 10,
            'tax_amount' => 0,
            'created_by' => $this->userId,
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides);
    }
}
