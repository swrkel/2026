<?php

namespace Tests\Feature\Petro;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Modules\Petro\Services\SettlementPaymentEditService;
use Modules\Petro\Services\SettlementPaymentReconciler;

/**
 * IS1313 (14 May 2026) — verbatim:
 *   "After editing the card payment, the all card amounts shows as duplicated in
 *    the Petro PD / List PD Settlement / Action / Edit and Edit no change."
 *   "After editing cash payments in the above page and saving the PD Settlement,
 *    the edited amount shows as duplicated in the accounting module / list
 *    accounts / cash account book. Need to fix."
 *   "After saving the PD Settlement, the related credit sales payments are
 *    duplicated in the contact / customer / action / ledger."
 *
 * Root cause: SettlementPaymentEditService::editCashPayment / editCardPayment /
 * editChequePayment only update the settlement_*_payments row. They do NOT
 * cascade to the linked Transaction.final_total, ContactLedger.amount, or
 * AccountTransaction.amount. So after an edit, the accounting tables retain
 * the old amount alongside the new one — visible as a duplicate row in the
 * card account book / cash account book / customer ledger.
 *
 * Each test seeds the legacy state (scsp row + linked Transaction + AT + ledger
 * with the OLD amount), invokes the EditService with a new amount, and asserts
 * the cascade left exactly one row per accounting table with the NEW amount.
 *
 * @group characterization
 */
class Is1313EditCascadeNoDuplicateTest extends PetroTestCase
{
    use DatabaseTransactions;

    /** @test */
    public function editing_a_card_payment_updates_linked_transaction_and_account_transaction(): void
    {
        $pumpPaymentId = $this->seedPumpOperatorPayment([
            'payment_type'   => 'card',
            'payment_amount' => '1000.00',
        ]);
        $settlementNo = 'PDST-IS1313-' . uniqid();

        // Seed the linked Transaction + AT + ContactLedger with the OLD amount.
        $transactionId = DB::table('transactions')->insertGetId([
            'business_id'      => $this->businessId,
            'location_id'      => null,
            'type'             => 'settlement',
            'sub_type'         => 'card_payment',
            'status'           => 'final',
            'payment_status'   => 'paid',
            'invoice_no'       => $settlementNo,
            'ref_no'           => 'PD Card Payment #IS1313',
            'transaction_date' => now(),
            'total_before_tax' => 1000,
            'final_total'      => 1000,
            'tax_amount'       => 0,
            'created_by'       => $this->userId,
        ]);
        $atId = DB::table('account_transactions')->insertGetId([
            'account_id'     => 1,
            'business_id'    => $this->businessId,
            'type'           => 'debit',
            'amount'         => 1000,
            'operation_date' => now(),
            'created_by'     => $this->userId,
            'transaction_id' => $transactionId,
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);
        $ledgerId = DB::table('contact_ledgers')->insertGetId([
            'transaction_id' => $transactionId,
            'contact_id'     => $this->contactId,
            'type'           => 'credit',
            'amount'         => 1000,
            'operation_date' => now(),
            'created_by'     => $this->userId,
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        // Seed the settlement_card_payments row linked to the same transaction.
        $card = app(SettlementPaymentReconciler::class)->upsertOne(
            $this->businessId,
            $settlementNo,
            'settlement_card_payments',
            [
                'customer_id'         => $this->contactId,
                'amount'              => 1000.0,
                'card_type'           => 1,
                'customer_payment_id' => null,
                'pump_payment_id'     => $pumpPaymentId,
                'transaction_id'      => $transactionId,
            ]
        );

        // Now perform the edit: amount changes 1000 -> 1200.
        app(SettlementPaymentEditService::class)->editCardPayment(
            $this->businessId,
            $card->id,
            ['amount' => 1200.0]
        );

        // The settlement_card_payments row updated:
        $this->assertEqualsWithDelta(
            1200.0,
            (float) DB::table('settlement_card_payments')->where('id', $card->id)->value('amount'),
            0.001
        );

        // The linked Transaction must reflect the new amount (was 1000):
        $this->assertEqualsWithDelta(
            1200.0,
            (float) DB::table('transactions')->where('id', $transactionId)->value('final_total'),
            0.001,
            'IS1313: editCardPayment must cascade to Transaction.final_total.'
        );

        // The AccountTransaction row must reflect the new amount (was 1000):
        $this->assertEqualsWithDelta(
            1200.0,
            (float) DB::table('account_transactions')->where('id', $atId)->value('amount'),
            0.001,
            'IS1313: editCardPayment must cascade to AccountTransaction.amount so the card account book does not show duplicates.'
        );

        // The ContactLedger row must reflect the new amount (was 1000):
        $this->assertEqualsWithDelta(
            1200.0,
            (float) DB::table('contact_ledgers')->where('id', $ledgerId)->value('amount'),
            0.001,
            'IS1313: editCardPayment must cascade to ContactLedger.amount so the customer ledger does not show duplicates.'
        );

        // Critically: there must be exactly ONE AccountTransaction for this transaction.
        // If the editCascade incorrectly INSERTs a new row instead of updating, the count
        // jumps to 2 and the card account book shows two debits.
        $this->assertSame(
            1,
            DB::table('account_transactions')->where('transaction_id', $transactionId)->count(),
            'IS1313: edit must UPDATE the linked AT, not INSERT a sibling.'
        );
    }

    /** @test */
    public function editing_a_cash_payment_cascades_to_transaction_and_account_transaction(): void
    {
        $pumpPaymentId = $this->seedPumpOperatorPayment([
            'payment_type'   => 'cash',
            'payment_amount' => '2000.00',
        ]);
        $settlementNo = 'PDST-IS1313-CASH-' . uniqid();

        $transactionId = DB::table('transactions')->insertGetId([
            'business_id'      => $this->businessId,
            'location_id'      => null,
            'type'             => 'settlement',
            'sub_type'         => 'cash_payment',
            'status'           => 'final',
            'payment_status'   => 'paid',
            'invoice_no'       => $settlementNo,
            'ref_no'           => 'PD Cash Payment #IS1313',
            'transaction_date' => now(),
            'total_before_tax' => 2000,
            'final_total'      => 2000,
            'tax_amount'       => 0,
            'created_by'       => $this->userId,
        ]);
        $atId = DB::table('account_transactions')->insertGetId([
            'account_id'     => 1,
            'business_id'    => $this->businessId,
            'type'           => 'debit',
            'amount'         => 2000,
            'operation_date' => now(),
            'created_by'     => $this->userId,
            'transaction_id' => $transactionId,
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        $cash = app(SettlementPaymentReconciler::class)->upsertOne(
            $this->businessId,
            $settlementNo,
            'settlement_cash_payments',
            [
                'customer_id'         => $this->contactId,
                'amount'              => 2000.0,
                'customer_payment_id' => null,
                'pump_payment_id'     => $pumpPaymentId,
                'transaction_id'      => $transactionId,
            ]
        );

        app(SettlementPaymentEditService::class)->editCashPayment(
            $this->businessId,
            $cash->id,
            ['amount' => 2500.0]
        );

        $this->assertEqualsWithDelta(
            2500.0,
            (float) DB::table('transactions')->where('id', $transactionId)->value('final_total'),
            0.001,
            'IS1313: editCashPayment must cascade to Transaction.final_total.'
        );
        $this->assertEqualsWithDelta(
            2500.0,
            (float) DB::table('account_transactions')->where('id', $atId)->value('amount'),
            0.001,
            'IS1313: editCashPayment must cascade to AccountTransaction.amount.'
        );
        $this->assertSame(
            1,
            DB::table('account_transactions')->where('transaction_id', $transactionId)->count(),
            'IS1313: cash edit must UPDATE, not INSERT.'
        );
    }

    /** @test */
    public function editing_a_cheque_payment_cascades_to_transaction_and_account_transaction(): void
    {
        $pumpPaymentId = $this->seedPumpOperatorPayment([
            'payment_type'   => 'cheque',
            'payment_amount' => '3000.00',
        ]);
        $settlementNo = 'PDST-IS1313-CHQ-' . uniqid();

        $transactionId = DB::table('transactions')->insertGetId([
            'business_id'      => $this->businessId,
            'location_id'      => null,
            'type'             => 'settlement',
            'sub_type'         => 'cheque_payment',
            'status'           => 'final',
            'payment_status'   => 'paid',
            'invoice_no'       => $settlementNo,
            'ref_no'           => 'PD Cheque Payment #IS1313',
            'transaction_date' => now(),
            'total_before_tax' => 3000,
            'final_total'      => 3000,
            'tax_amount'       => 0,
            'created_by'       => $this->userId,
        ]);
        $atId = DB::table('account_transactions')->insertGetId([
            'account_id'     => 1,
            'business_id'    => $this->businessId,
            'type'           => 'debit',
            'amount'         => 3000,
            'operation_date' => now(),
            'created_by'     => $this->userId,
            'transaction_id' => $transactionId,
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        $cheque = app(SettlementPaymentReconciler::class)->upsertOne(
            $this->businessId,
            $settlementNo,
            'settlement_cheque_payments',
            [
                'customer_id'         => $this->contactId,
                'bank_name'           => 'TestBank',
                'cheque_number'       => 'IS1313-CHQ',
                'cheque_date'         => now()->toDateString(),
                'amount'              => 3000.0,
                'customer_payment_id' => null,
                'pump_payment_id'     => $pumpPaymentId,
                'transaction_id'      => $transactionId,
            ]
        );

        app(SettlementPaymentEditService::class)->editChequePayment(
            $this->businessId,
            $cheque->id,
            ['amount' => 3500.0]
        );

        $this->assertEqualsWithDelta(
            3500.0,
            (float) DB::table('transactions')->where('id', $transactionId)->value('final_total'),
            0.001
        );
        $this->assertEqualsWithDelta(
            3500.0,
            (float) DB::table('account_transactions')->where('id', $atId)->value('amount'),
            0.001
        );
        $this->assertSame(
            1,
            DB::table('account_transactions')->where('transaction_id', $transactionId)->count()
        );
    }

    /**
     * @test
     *
     * IS1313 verbatim: "After saving the PD Settlement, the related credit sales
     * payments are duplicated in the contact / customer / action / ledger."
     *
     * editCreditSale already cascades ContactLedger via transaction_id. Confirm
     * my refactor of the editX methods didn't regress that pre-existing cascade.
     */
    public function editing_a_credit_sale_does_not_create_duplicate_contact_ledger_rows(): void
    {
        $pumpPaymentId = $this->seedPumpOperatorPayment([
            'payment_type'   => 'credit',
            'payment_amount' => '5000.00',
        ]);
        $settlementNo = 'PDST-IS1313-CRED-' . uniqid();

        $transactionId = DB::table('transactions')->insertGetId([
            'business_id'      => $this->businessId,
            'type'             => 'sell',
            'sub_type'         => 'credit_sale',
            'status'           => 'final',
            'payment_status'   => 'due',
            'invoice_no'       => $settlementNo,
            'ref_no'           => 'PD Credit Sale #IS1313',
            'transaction_date' => now(),
            'final_total'      => 5000,
            'total_before_tax' => 5000,
            'tax_amount'       => 0,
            'created_by'       => $this->userId,
        ]);
        DB::table('contact_ledgers')->insert([
            'transaction_id' => $transactionId,
            'contact_id'     => $this->contactId,
            'type'           => 'debit',
            'amount'         => 5000,
            'operation_date' => now(),
            'created_by'     => $this->userId,
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        $scsp = app(SettlementPaymentReconciler::class)->upsertOne(
            $this->businessId,
            $settlementNo,
            'settlement_credit_sale_payments',
            $this->buildCreditSalePaymentData([
                'amount'           => 5000,
                'sub_total'        => 5000,
                'total_discount'   => 0,
                'pump_payment_id'  => $pumpPaymentId,
                'transaction_id'   => $transactionId,
            ])
        );

        app(SettlementPaymentEditService::class)->editCreditSale(
            $this->businessId,
            $scsp->id,
            [
                'amount'         => 5500,
                'sub_total'      => 5500,
                'total_discount' => 0,
            ]
        );

        $ledgerRows = DB::table('contact_ledgers')->where('transaction_id', $transactionId)->get();
        $this->assertCount(
            1,
            $ledgerRows,
            'IS1313: editing a credit sale must NOT create duplicate ContactLedger rows.'
        );
        $this->assertEqualsWithDelta(
            5500.0,
            (float) $ledgerRows->first()->amount,
            0.001,
            'IS1313: the single ContactLedger row must reflect the edited amount.'
        );
    }

    /**
     * @test
     *
     * IS1313 verbatim: "Real card amounts (before edit): 1000 / 2000 / 3500 / 7500
     *                   Card amounts (after edit): 1200 / 1950 / 3500 / 7500"
     *
     * Editing two of four card payments must leave the un-edited two byte-identical
     * in the AccountTransaction table. The duplicate-display symptom comes from the
     * un-edited rows being touched somehow during an edit cascade. Pin that down.
     */
    public function editing_some_card_payments_leaves_un_edited_siblings_untouched(): void
    {
        $settlementNo = 'PDST-IS1313-SIBLING-' . uniqid();
        $reconciler   = app(SettlementPaymentReconciler::class);
        $editService  = app(SettlementPaymentEditService::class);

        // Build 4 card payments with linked Transaction+AT each.
        $cards = [];
        foreach ([1000, 2000, 3500, 7500] as $i => $amount) {
            $popId = $this->seedPumpOperatorPayment([
                'payment_type'   => 'card',
                'payment_amount' => (string) $amount,
            ]);
            $txId = DB::table('transactions')->insertGetId([
                'business_id'      => $this->businessId,
                'type'             => 'settlement',
                'sub_type'         => 'card_payment',
                'status'           => 'final',
                'payment_status'   => 'paid',
                'invoice_no'       => $settlementNo,
                'ref_no'           => 'PD Card Payment #IS1313-' . $i,
                'transaction_date' => now(),
                'final_total'      => $amount,
                'total_before_tax' => $amount,
                'tax_amount'       => 0,
                'created_by'       => $this->userId,
            ]);
            DB::table('account_transactions')->insert([
                'account_id'     => 1,
                'business_id'    => $this->businessId,
                'type'           => 'debit',
                'amount'         => $amount,
                'operation_date' => now(),
                'created_by'     => $this->userId,
                'transaction_id' => $txId,
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);
            $card = $reconciler->upsertOne(
                $this->businessId,
                $settlementNo,
                'settlement_card_payments',
                [
                    'customer_id'         => $this->contactId,
                    'amount'              => $amount,
                    'card_type'           => 1,
                    'customer_payment_id' => null,
                    'pump_payment_id'     => $popId,
                    'transaction_id'      => $txId,
                ]
            );
            $cards[] = ['card' => $card, 'tx_id' => $txId];
        }

        // Edit only cards 0 and 1: 1000 -> 1200, 2000 -> 1950.
        $editService->editCardPayment($this->businessId, $cards[0]['card']->id, ['amount' => 1200.0]);
        $editService->editCardPayment($this->businessId, $cards[1]['card']->id, ['amount' => 1950.0]);

        // Un-edited cards 2 (3500) and 3 (7500) must be byte-identical post-edit.
        $this->assertEqualsWithDelta(
            3500.0,
            (float) DB::table('transactions')->where('id', $cards[2]['tx_id'])->value('final_total'),
            0.001,
            'IS1313: un-edited card 3500 transaction must NOT change.'
        );
        $this->assertEqualsWithDelta(
            7500.0,
            (float) DB::table('transactions')->where('id', $cards[3]['tx_id'])->value('final_total'),
            0.001,
            'IS1313: un-edited card 7500 transaction must NOT change.'
        );
        $this->assertEqualsWithDelta(
            3500.0,
            (float) DB::table('account_transactions')->where('transaction_id', $cards[2]['tx_id'])->value('amount'),
            0.001
        );
        $this->assertEqualsWithDelta(
            7500.0,
            (float) DB::table('account_transactions')->where('transaction_id', $cards[3]['tx_id'])->value('amount'),
            0.001
        );

        // And critically — exactly one AT per transaction, no proliferation.
        foreach ($cards as $entry) {
            $this->assertSame(
                1,
                DB::table('account_transactions')->where('transaction_id', $entry['tx_id'])->count(),
                "IS1313: transaction {$entry['tx_id']} must have exactly one AccountTransaction row, not duplicated."
            );
        }
    }

    /**
     * @test
     *
     * IS1313: "After editing the card payment, the all card amounts shows as
     * duplicated in the Petro PD / List PD Settlement / Action / Edit".
     *
     * This is the READ side. After an edit cascade, when the PD Edit view loads
     * the Settlement's card_payments relation, it must return exactly one row
     * per (settlement, source pump payment). No Cartesian-product duplication.
     */
    public function pd_edit_view_card_payments_relation_returns_one_row_per_logical_payment_after_edit(): void
    {
        $settlementNo = 'PDST-IS1313-READ-' . uniqid();
        $locationId = (int) DB::table('business_locations')
            ->where('business_id', $this->businessId)
            ->value('id');
        if (! $locationId) {
            $this->markTestSkipped('No business_location.');
        }

        $settlementId = DB::table('settlements')->insertGetId([
            'settlement_no'      => $settlementNo,
            'business_id'        => $this->businessId,
            'transaction_date'   => now()->toDateString(),
            'finish_date'        => now()->toDateString(),
            'location_id'        => $locationId,
            'pump_operator_id'   => $this->pumpOperatorId,
            'bulk_store_product' => 0,
            'work_shift'         => json_encode([]),
            'total_amount'       => '4000',
            'status'             => 0,
            'created_at'         => now(),
            'updated_at'         => now(),
        ]);

        // Seed 4 distinct card payments for the same settlement.
        $reconciler = app(SettlementPaymentReconciler::class);
        $cardIds = [];
        foreach ([1000, 2000, 3000, 4000] as $amount) {
            $popId = $this->seedPumpOperatorPayment([
                'payment_type' => 'card', 'payment_amount' => (string) $amount,
            ]);
            $card = $reconciler->upsertOne(
                $this->businessId, (string) $settlementId, 'settlement_card_payments',
                [
                    'customer_id'         => $this->contactId,
                    'amount'              => $amount,
                    'card_type'           => 1,
                    'customer_payment_id' => null,
                    'pump_payment_id'     => $popId,
                ]
            );
            $cardIds[] = $card->id;
        }

        // Edit one of them via the EditService (cascade now active).
        app(SettlementPaymentEditService::class)->editCardPayment(
            $this->businessId, $cardIds[1], ['amount' => 2222.0]
        );

        // Load the Settlement freshly and read the card_payments relation
        // through the same DB query the PD Edit view fallback at
        // SettlementPDController.php:3447-3470 uses.
        $card_payments = \Modules\Petro\Entities\SettlementCardPayment::where(function ($q) use ($settlementId, $settlementNo) {
            $q->where('settlement_card_payments.settlement_no', $settlementNo)
                ->orWhere('settlement_card_payments.settlement_no', (string) $settlementId);
        })->get();

        $this->assertCount(
            4,
            $card_payments,
            'IS1313: PD Edit must show exactly 4 card payment rows for 4 distinct cards, not duplicated.'
        );

        // Each card's amount matches the expected (edited or untouched) value.
        $amounts = $card_payments->pluck('amount')->map(fn($a) => (float) $a)->sort()->values()->all();
        $this->assertEqualsWithDelta([1000.0, 2222.0, 3000.0, 4000.0], $amounts, 0.001);
    }
}
