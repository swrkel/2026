<?php

namespace Tests\Feature\Petro;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;

/**
 * @group characterization
 */
class Phase2BackfillTest extends PetroTestCase
{
    use DatabaseTransactions;

    /** @test */
    public function payment_backfill_populates_pump_payment_id_for_matching_legacy_rows(): void
    {
        $settlementNo = 'TST-P2-PAY-' . uniqid();
        $cardPopId = $this->seedPumpOperatorPayment([
            'payment_type' => 'card',
            'payment_amount' => '123.450000',
            'settlement_no' => $settlementNo,
        ]);
        $cashPopId = $this->seedPumpOperatorPayment([
            'payment_type' => 'cash',
            'payment_amount' => '234.560000',
            'settlement_no' => $settlementNo,
        ]);
        $chequePopId = $this->seedPumpOperatorPayment([
            'payment_type' => 'cheque',
            'payment_amount' => '345.670000',
            'settlement_no' => $settlementNo,
        ]);
        $creditPopId = $this->seedPumpOperatorPayment([
            'payment_type' => 'credit',
            'payment_amount' => '456.780000',
            'settlement_no' => $settlementNo,
            'collection_form_no' => 'P2-COL-' . uniqid(),
        ]);
        $creditPop = DB::table('pump_operator_payments')->where('id', $creditPopId)->first();

        $cardId = DB::table('settlement_card_payments')->insertGetId([
            'business_id' => $this->businessId,
            'settlement_no' => $settlementNo,
            'customer_id' => $this->contactId,
            'amount' => 123.45,
            'card_type' => 1,
            'pump_payment_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $cashId = DB::table('settlement_cash_payments')->insertGetId([
            'business_id' => $this->businessId,
            'settlement_no' => $settlementNo,
            'customer_id' => $this->contactId,
            'amount' => 234.56,
            'pump_payment_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $chequeId = DB::table('settlement_cheque_payments')->insertGetId([
            'business_id' => $this->businessId,
            'settlement_no' => $settlementNo,
            'customer_id' => $this->contactId,
            'bank_name' => 'Test Bank',
            'cheque_number' => 'P2-' . random_int(1000, 9999),
            'cheque_date' => now()->toDateString(),
            'amount' => 345.67,
            'pump_payment_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $creditId = DB::table('settlement_credit_sale_payments')->insertGetId([
            'business_id' => $this->businessId,
            'settlement_no' => $settlementNo,
            'customer_id' => $this->contactId,
            'product_id' => $this->productId,
            'pump_operator_id' => $this->pumpOperatorId,
            'order_number' => 'P2-' . uniqid(),
            'order_date' => now()->toDateString(),
            'price' => 456.78,
            'discount' => 0,
            'total_discount' => 0,
            'sub_total' => 456.78,
            'qty' => 1,
            'amount' => 456.78,
            'is_from_pumper' => 1,
            'collection_form_no' => $creditPop->collection_form_no,
            'pump_payment_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $beforeTotal = DB::table('settlement_card_payments')
            ->where('settlement_no', $settlementNo)
            ->sum('amount');

        $this->runMigration('2026_05_14_000001_backfill_pump_payment_id_on_legacy_settlement_rows.php');

        $card = DB::table('settlement_card_payments')->where('id', $cardId)->first();
        $cash = DB::table('settlement_cash_payments')->where('id', $cashId)->first();
        $cheque = DB::table('settlement_cheque_payments')->where('id', $chequeId)->first();
        $credit = DB::table('settlement_credit_sale_payments')->where('id', $creditId)->first();
        $afterTotal = DB::table('settlement_card_payments')
            ->where('settlement_no', $settlementNo)
            ->sum('amount');

        $this->assertSame($cardPopId, (int) $card->pump_payment_id);
        $this->assertSame($cashPopId, (int) $cash->pump_payment_id);
        $this->assertSame($chequePopId, (int) $cheque->pump_payment_id);
        $this->assertSame($creditPopId, (int) $credit->pump_payment_id);
        $this->assertEqualsWithDelta((float) $beforeTotal, (float) $afterTotal, 0.001);
    }

    /** @test */
    public function assignment_backfill_links_finalized_settlement_assignments(): void
    {
        $shiftNumber = (string) random_int(7000, 9000);
        $settlementId = DB::table('settlements')->insertGetId([
            'settlement_no' => 'TST-P2-ASG-' . uniqid(),
            'business_id' => $this->businessId,
            'transaction_date' => now()->toDateString(),
            'finish_date' => now()->toDateString(),
            'location_id' => 1,
            'pump_operator_id' => $this->pumpOperatorId,
            'bulk_store_product' => 0,
            'work_shift' => json_encode([$shiftNumber]),
            'total_amount' => '0',
            'status' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $assignmentId = DB::table('pump_operator_assignments')->insertGetId([
            'business_id' => $this->businessId,
            'pump_operator_id' => $this->pumpOperatorId,
            'shift_id' => random_int(7000, 9000),
            'shift_number' => $shiftNumber,
            'status' => 'close',
            'settlement_id' => null,
            'closed_in_settlement' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->runMigration('2026_05_14_000002_backfill_assignment_settlement_link.php');

        $assignment = DB::table('pump_operator_assignments')->where('id', $assignmentId)->first();
        $this->assertSame($settlementId, (int) $assignment->settlement_id);
        $this->assertSame(1, (int) $assignment->closed_in_settlement);
    }

    /** @test */
    public function transaction_backfill_populates_transaction_and_account_transaction_fk(): void
    {
        $settlementNo = 'TST-P2-TX-' . uniqid();
        $settlementId = DB::table('settlements')->insertGetId([
            'settlement_no' => $settlementNo,
            'business_id' => $this->businessId,
            'transaction_date' => now()->toDateString(),
            'finish_date' => now()->toDateString(),
            'location_id' => 1,
            'pump_operator_id' => $this->pumpOperatorId,
            'bulk_store_product' => 0,
            'work_shift' => json_encode(['1']),
            'total_amount' => '0',
            'status' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $transactionId = DB::table('transactions')->insertGetId([
            'business_id' => $this->businessId,
            'location_id' => null,
            'type' => 'sell',
            'status' => 'final',
            'payment_status' => 'paid',
            'invoice_no' => $settlementNo,
            'ref_no' => 'Settlement No: ' . $settlementNo,
            'transaction_date' => now(),
            'total_before_tax' => 100,
            'final_total' => 100,
            'tax_amount' => 0,
            'created_by' => $this->userId,
            'petro_settlement_id' => null,
        ]);

        $accountTransactionId = DB::table('account_transactions')->insertGetId([
            'account_id' => 1,
            'business_id' => $this->businessId,
            'type' => 'debit',
            'amount' => 100,
            'operation_date' => now(),
            'created_by' => $this->userId,
            'transaction_id' => $transactionId,
            'petro_settlement_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->runMigration('2026_05_14_000003_backfill_petro_settlement_id_on_transactions.php');

        $transaction = DB::table('transactions')->where('id', $transactionId)->first();
        $accountTransaction = DB::table('account_transactions')->where('id', $accountTransactionId)->first();

        $this->assertSame($settlementId, (int) $transaction->petro_settlement_id);
        $this->assertSame($settlementId, (int) $accountTransaction->petro_settlement_id);
    }

    private function runMigration(string $file): void
    {
        $migration = require base_path('database/migrations/' . $file);
        $migration->up();
    }
}
