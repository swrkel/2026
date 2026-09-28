<?php

namespace Tests\Feature\Petro;

use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Modules\Petro\Entities\Settlement;
use Modules\Petro\Http\Controllers\AddPaymentController;

class AddPaymentCardPaymentPumpKeyTest extends PetroTestCase
{
    use DatabaseTransactions;

    /** @test */
    public function save_card_payment_uses_pump_payment_id_before_reconciler_write(): void
    {
        $settlement = $this->seedSettlement();
        $pumpPaymentId = $this->seedPumpOperatorPayment([
            'payment_type' => 'card',
            'payment_amount' => 320,
            'pump_operator_id' => $this->pumpOperatorId,
            'settlement_no' => null,
            'is_used' => 0,
        ]);

        $this->actingAs(User::where('business_id', $this->businessId)->firstOrFail())
            ->withSession([
                'business.id' => $this->businessId,
                'user.business_id' => $this->businessId,
                'user.id' => $this->userId,
            ])
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->postJson('/petro/settlement/payment/save-card-payment', [
                'settlement_no' => $settlement->settlement_no,
                'customer_id' => $this->contactId,
                'amount' => 320,
                'card_type' => 1,
                'card_number' => '111111',
                'slip_no' => 'PUMP-' . $pumpPaymentId,
                'note' => 'card from payment-to-finalize',
                'pump_payment_id' => $pumpPaymentId,
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $rows = DB::table('settlement_card_payments')
            ->where('business_id', $this->businessId)
            ->where('settlement_no', (string) $settlement->id)
            ->get();

        $this->assertCount(1, $rows);
        $this->assertSame($pumpPaymentId, (int) $rows->first()->pump_payment_id);
        $this->assertSame(0, DB::table('settlement_card_payments')
            ->where('business_id', $this->businessId)
            ->where('settlement_no', (string) $settlement->id)
            ->whereNull('pump_payment_id')
            ->count());
    }

    /** @test */
    public function add_daily_cards_uses_matching_pump_payment_id_before_reconciler_write(): void
    {
        $settlement = $this->seedSettlement();
        $shiftId = DB::table('petro_shifts')->insertGetId([
            'business_id' => $this->businessId,
            'pump_operator_id' => $this->pumpOperatorId,
            'status' => 1,
            'shift_date' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $collectionNo = (string) (900000 + random_int(1, 9999));
        $pumpPaymentId = $this->seedPumpOperatorPayment([
            'payment_type' => 'card',
            'payment_amount' => 130,
            'pump_operator_id' => $this->pumpOperatorId,
            'collection_form_no' => $collectionNo,
            'shift_id' => $shiftId,
            'settlement_no' => null,
            'is_used' => 0,
        ]);

        $dailyCardId = DB::table('daily_cards')->insertGetId([
            'business_id' => $this->businessId,
            'pump_operator_id' => $this->pumpOperatorId,
            'collection_no' => $collectionNo,
            'amount' => 130,
            'card_type' => 1,
            'card_number' => '111111',
            'customer_id' => $this->contactId,
            'slip_no' => 'SLIP-' . $pumpPaymentId,
            'date' => now()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        app(AddPaymentController::class)->addDailyCards(
            $settlement->id,
            $this->pumpOperatorId,
            $this->businessId,
            $shiftId
        );

        $row = DB::table('settlement_card_payments')
            ->where('business_id', $this->businessId)
            ->where('settlement_no', (string) $settlement->id)
            ->first();

        $this->assertNotNull($row);
        $this->assertSame($pumpPaymentId, (int) $row->pump_payment_id);
        $this->assertSame(0, DB::table('settlement_card_payments')
            ->where('business_id', $this->businessId)
            ->where('settlement_no', (string) $settlement->id)
            ->whereNull('pump_payment_id')
            ->count());
    }

    /** @test */
    public function delete_card_payment_removes_linked_accounting_rows_for_finalized_pd_edit(): void
    {
        $settlement = $this->seedSettlement();
        $amount = 4100.00;

        $transactionId = DB::table('transactions')->insertGetId([
            'business_id' => $this->businessId,
            'location_id' => $settlement->location_id,
            'type' => 'settlement',
            'sub_type' => 'card_payment',
            'status' => 'final',
            'payment_status' => 'paid',
            'contact_id' => $this->contactId,
            'pump_operator_id' => $this->pumpOperatorId,
            'transaction_date' => now(),
            'total_before_tax' => $amount,
            'final_total' => $amount,
            'tax_amount' => 0,
            'invoice_no' => $settlement->settlement_no,
            'ref_no' => 'PD Card Payment #delete-test',
            'created_by' => $this->userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $transactionPaymentId = DB::table('transaction_payments')->insertGetId([
            'transaction_id' => $transactionId,
            'business_id' => $this->businessId,
            'amount' => $amount,
            'method' => 'card',
            'card_type' => '1',
            'card_number' => '411111',
            'paid_on' => now(),
            'created_by' => $this->userId,
            'payment_for' => $this->contactId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('account_transactions')->insert([
            'account_id' => 1,
            'business_id' => $this->businessId,
            'type' => 'debit',
            'sub_type' => 'ledger_show',
            'amount' => $amount,
            'operation_date' => now(),
            'created_by' => $this->userId,
            'transaction_id' => $transactionId,
            'transaction_payment_id' => $transactionPaymentId,
            'note' => 'Settlement No: ' . $settlement->settlement_no . ' | Card Payment',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('contact_ledgers')->insert([
            'transaction_id' => $transactionId,
            'transaction_payment_id' => $transactionPaymentId,
            'contact_id' => $this->contactId,
            'type' => 'credit',
            'amount' => $amount,
            'operation_date' => now(),
            'created_by' => $this->userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $cardPaymentId = DB::table('settlement_card_payments')->insertGetId([
            'business_id' => $this->businessId,
            'settlement_no' => $settlement->id,
            'customer_id' => $this->contactId,
            'card_type' => 1,
            'card_number' => '411111',
            'amount' => $amount,
            'customer_payment_id' => $transactionPaymentId,
            'transaction_id' => $transactionId,
            'slip_no' => 'DEL-' . uniqid(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs(User::where('business_id', $this->businessId)->firstOrFail())
            ->withSession([
                'business.id' => $this->businessId,
                'user.business_id' => $this->businessId,
                'user.id' => $this->userId,
            ])
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->deleteJson('/petro/settlement/payment/delete-card-payment/' . $cardPaymentId, [
                'is_edit' => 1,
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('settlement_card_payments', ['id' => $cardPaymentId]);
        $this->assertDatabaseMissing('account_transactions', ['transaction_id' => $transactionId]);
        $this->assertDatabaseMissing('contact_ledgers', ['transaction_id' => $transactionId]);
        $this->assertDatabaseMissing('transaction_payments', ['id' => $transactionPaymentId]);
        $this->assertDatabaseMissing('transactions', ['id' => $transactionId]);
    }

    private function seedSettlement(): Settlement
    {
        $locationId = (int) DB::table('business_locations')
            ->where('business_id', $this->businessId)
            ->value('id');

        $id = DB::table('settlements')->insertGetId([
            'settlement_no' => 'PDST-CARD-' . uniqid(),
            'business_id' => $this->businessId,
            'transaction_date' => now()->toDateString(),
            'finish_date' => now()->toDateString(),
            'location_id' => $locationId ?: null,
            'pump_operator_id' => $this->pumpOperatorId,
            'work_shift' => json_encode([]),
            'status' => 0,
            'is_edit' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Settlement::findOrFail($id);
    }
}
