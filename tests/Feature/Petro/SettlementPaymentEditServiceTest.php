<?php

namespace Tests\Feature\Petro;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Modules\Petro\Services\SettlementPaymentEditService;
use Modules\Petro\Services\SettlementPaymentReconciler;

/**
 * @group characterization
 */
class SettlementPaymentEditServiceTest extends PetroTestCase
{
    use DatabaseTransactions;

    /** @test */
    public function edit_credit_sale_updates_the_existing_row_by_id_and_cascades_linked_totals(): void
    {
        $pumpPaymentId = $this->seedPumpOperatorPayment([
            'payment_type' => 'credit',
            'payment_amount' => '100.00',
            'collection_form_no' => 'PH4-' . uniqid(),
        ]);
        $settlementNo = 'PH4-' . uniqid();

        $payment = app(SettlementPaymentReconciler::class)->upsertOne(
            $this->businessId,
            $settlementNo,
            'settlement_credit_sale_payments',
            $this->buildCreditSalePaymentData([
                'amount' => 100,
                'sub_total' => 100,
                'total_discount' => 0,
                'collection_form_no' => 'PH4-FORM',
                'pump_payment_id' => $pumpPaymentId,
            ])
        );

        $dailyCollectionId = DB::table('daily_collections')->insertGetId([
            'business_id' => $this->businessId,
            'pump_operator_id' => $this->pumpOperatorId,
            'collection_form_no' => 'PH4-FORM',
            'type' => 'daily_voucher',
            'current_amount' => 100,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        app(SettlementPaymentEditService::class)->editCreditSale($this->businessId, $payment->id, [
            'amount' => 150,
            'sub_total' => 140,
            'total_discount' => 10,
            'price' => 150,
            'discount' => 10,
            'qty' => 1,
        ]);

        $rows = DB::table('settlement_credit_sale_payments')
            ->where('business_id', $this->businessId)
            ->where('pump_payment_id', $pumpPaymentId)
            ->get();

        $this->assertCount(1, $rows);
        $this->assertSame('150.0000', number_format((float) $rows->first()->amount, 4, '.', ''));
        $this->assertSame('150.0000', number_format((float) DB::table('pump_operator_payments')->where('id', $pumpPaymentId)->value('payment_amount'), 4, '.', ''));
        $this->assertSame('140.0000', number_format((float) DB::table('daily_collections')->where('id', $dailyCollectionId)->value('current_amount'), 4, '.', ''));
    }

    /** @test */
    public function edit_card_cash_and_cheque_update_existing_rows_without_guard_bypass(): void
    {
        $settlementNo = 'PH4-' . uniqid();

        $card = app(SettlementPaymentReconciler::class)->upsertOne($this->businessId, $settlementNo, 'settlement_card_payments', [
            'amount' => 10,
            'customer_id' => $this->contactId,
            'pump_payment_id' => $this->seedPumpOperatorPayment(['payment_type' => 'card']),
        ]);
        $cash = app(SettlementPaymentReconciler::class)->upsertOne($this->businessId, $settlementNo, 'settlement_cash_payments', [
            'amount' => 20,
            'customer_id' => $this->contactId,
            'pump_payment_id' => $this->seedPumpOperatorPayment(['payment_type' => 'cash']),
        ]);
        $cheque = app(SettlementPaymentReconciler::class)->upsertOne($this->businessId, $settlementNo, 'settlement_cheque_payments', [
            'amount' => 30,
            'customer_id' => $this->contactId,
            'pump_payment_id' => $this->seedPumpOperatorPayment(['payment_type' => 'cheque']),
        ]);

        app(SettlementPaymentEditService::class)->editCardPayment($this->businessId, $card->id, ['amount' => 11]);
        app(SettlementPaymentEditService::class)->editCashPayment($this->businessId, $cash->id, ['amount' => 21]);
        app(SettlementPaymentEditService::class)->editChequePayment($this->businessId, $cheque->id, ['amount' => 31]);

        $this->assertSame('11.0000', number_format((float) DB::table('settlement_card_payments')->where('id', $card->id)->value('amount'), 4, '.', ''));
        $this->assertSame('21.0000', number_format((float) DB::table('settlement_cash_payments')->where('id', $cash->id)->value('amount'), 4, '.', ''));
        $this->assertSame('31.0000', number_format((float) DB::table('settlement_cheque_payments')->where('id', $cheque->id)->value('amount'), 4, '.', ''));
    }
}
