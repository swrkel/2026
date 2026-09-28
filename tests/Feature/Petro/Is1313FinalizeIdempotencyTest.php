<?php

namespace Tests\Feature\Petro;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Session\Store;
use Modules\Petro\Http\Controllers\SettlementPDController;
use Modules\Petro\Services\SettlementPaymentReconciler;

/**
 * IS1313 (14 May 2026) verbatim:
 *   "After saving the PD Settlement, the related card payments shows as
 *    duplicated in the accounting module / list accounts / card account book."
 *
 * Root cause: SettlementPDController::ensureSettlementCardAccounting uses
 * fuzzy amount+note matching for its dedup check. When the same settlement is
 * saved twice (or saved after an unrelated edit), the dedup either:
 *  (a) misses a stale row whose amount no longer matches the current value, OR
 *  (b) creates a new AccountTransaction row anyway because the prior matching
 *      row was wiped but the wipe path itself missed it.
 *
 * After the fix: regardless of how many times the user saves a settlement
 * without changing anything, there must be exactly ONE AccountTransaction row
 * per (settlement, card_payment) pair.
 *
 * @group characterization
 */
class Is1313FinalizeIdempotencyTest extends PetroTestCase
{
    use DatabaseTransactions;

    /** @test */
    public function calling_ensure_settlement_card_accounting_twice_does_not_create_duplicate_account_transactions(): void
    {
        $this->seedRequestSession();

        $settlementId = $this->seedFinalizedSettlement();
        $cardAccountId = $this->seedCardAccount();

        $popId = $this->seedPumpOperatorPayment([
            'payment_type'   => 'card',
            'payment_amount' => '1000.00',
        ]);

        $card = app(SettlementPaymentReconciler::class)->upsertOne(
            $this->businessId,
            (string) $settlementId,
            'settlement_card_payments',
            [
                'customer_id'         => $this->contactId,
                'amount'              => 1000.00,
                'card_type'           => $cardAccountId,
                'customer_payment_id' => null,
                'pump_payment_id'     => $popId,
            ]
        );

        $controller = app(SettlementPDController::class);
        $method = new \ReflectionMethod($controller, 'ensureSettlementCardAccounting');
        $method->setAccessible(true);

        $settlement = \Modules\Petro\Entities\Settlement::findOrFail($settlementId);
        $settlement->setRelation('card_payments', collect([
            \Modules\Petro\Entities\SettlementCardPayment::findOrFail($card->id)
        ]));

        // Invoke once — should create the Transaction + AT.
        $method->invoke($controller, $settlement, $this->businessId);

        $txCountAfterFirst = DB::table('transactions')
            ->where('invoice_no', $settlement->settlement_no)
            ->where('sub_type', 'card_payment')
            ->count();
        $atCountAfterFirst = DB::table('account_transactions')
            ->where('account_id', $cardAccountId)
            ->whereDate('operation_date', $settlement->transaction_date)
            ->count();

        $this->assertSame(1, $txCountAfterFirst, 'First call should create exactly 1 Transaction.');
        $this->assertSame(1, $atCountAfterFirst, 'First call should create exactly 1 AccountTransaction.');

        // Reload card payments fresh to mimic a second save flow.
        $settlement->setRelation('card_payments', collect([
            \Modules\Petro\Entities\SettlementCardPayment::findOrFail($card->id)
        ]));

        // Invoke a second time WITHOUT changing the amount — should NOT duplicate.
        $method->invoke($controller, $settlement, $this->businessId);

        $txCountAfterSecond = DB::table('transactions')
            ->where('invoice_no', $settlement->settlement_no)
            ->where('sub_type', 'card_payment')
            ->count();
        $atCountAfterSecond = DB::table('account_transactions')
            ->where('account_id', $cardAccountId)
            ->whereDate('operation_date', $settlement->transaction_date)
            ->count();

        $this->assertSame(
            1,
            $txCountAfterSecond,
            'IS1313: a second save with no changes must NOT create a duplicate Transaction.'
        );
        $this->assertSame(
            1,
            $atCountAfterSecond,
            'IS1313: a second save with no changes must NOT create a duplicate AccountTransaction in the card account book.'
        );
    }

    /** @test */
    public function calling_ensure_settlement_card_accounting_after_amount_edit_does_not_create_a_second_account_transaction(): void
    {
        $this->seedRequestSession();

        $settlementId = $this->seedFinalizedSettlement();
        $cardAccountId = $this->seedCardAccount();

        $popId = $this->seedPumpOperatorPayment([
            'payment_type'   => 'card',
            'payment_amount' => '1000.00',
        ]);

        $card = app(SettlementPaymentReconciler::class)->upsertOne(
            $this->businessId,
            (string) $settlementId,
            'settlement_card_payments',
            [
                'customer_id'         => $this->contactId,
                'amount'              => 1000.00,
                'card_type'           => $cardAccountId,
                'customer_payment_id' => null,
                'pump_payment_id'     => $popId,
            ]
        );

        $controller = app(SettlementPDController::class);
        $method = new \ReflectionMethod($controller, 'ensureSettlementCardAccounting');
        $method->setAccessible(true);

        $settlement = \Modules\Petro\Entities\Settlement::findOrFail($settlementId);
        $settlement->setRelation('card_payments', collect([
            \Modules\Petro\Entities\SettlementCardPayment::findOrFail($card->id)
        ]));

        // First call — creates Transaction + AT with amount 1000.
        $method->invoke($controller, $settlement, $this->businessId);

        // User edits the card payment amount to 1200 via EditService (this now cascades).
        app(\Modules\Petro\Services\SettlementPaymentEditService::class)->editCardPayment(
            $this->businessId,
            $card->id,
            ['amount' => 1200.00]
        );

        // Second save invocation — must NOT create a new AT.
        $settlement->setRelation('card_payments', collect([
            \Modules\Petro\Entities\SettlementCardPayment::findOrFail($card->id)
        ]));
        $method->invoke($controller, $settlement, $this->businessId);

        $atCount = DB::table('account_transactions')
            ->where('account_id', $cardAccountId)
            ->whereDate('operation_date', $settlement->transaction_date)
            ->count();
        $atRow = DB::table('account_transactions')
            ->where('account_id', $cardAccountId)
            ->whereDate('operation_date', $settlement->transaction_date)
            ->first();

        $this->assertSame(
            1,
            $atCount,
            'IS1313: after edit then re-save, card account book must have exactly one AT for this card.'
        );
        $this->assertEqualsWithDelta(
            1200.0,
            (float) $atRow->amount,
            0.001,
            'IS1313: the single remaining AT must reflect the EDITED amount, not the old one.'
        );
    }

    /** @test */
    public function card_accounting_collapses_duplicate_settlement_card_rows_by_pump_payment_identity(): void
    {
        $this->seedRequestSession();

        $settlementId = $this->seedFinalizedSettlement();
        $settlement = \Modules\Petro\Entities\Settlement::findOrFail($settlementId);
        $cardAccountId = $this->seedCardAccount();
        $pumpPaymentId = $this->seedPumpOperatorPayment([
            'payment_type' => 'card',
            'payment_amount' => '130.00',
        ]);

        $firstCard = app(SettlementPaymentReconciler::class)->upsertOne(
            $this->businessId,
            (string) $settlementId,
            'settlement_card_payments',
            [
                'customer_id' => $this->contactId,
                'amount' => 130.00,
                'card_type' => $cardAccountId,
                'customer_payment_id' => null,
                'pump_payment_id' => $pumpPaymentId,
            ]
        );

        $secondCard = app(SettlementPaymentReconciler::class)->upsertOne(
            $this->businessId,
            $settlement->settlement_no,
            'settlement_card_payments',
            [
                'customer_id' => $this->contactId,
                'amount' => 130.00,
                'card_type' => $cardAccountId,
                'customer_payment_id' => null,
                'pump_payment_id' => $pumpPaymentId,
            ]
        );

        $controller = app(SettlementPDController::class);
        $method = new \ReflectionMethod($controller, 'ensureSettlementCardAccounting');
        $method->setAccessible(true);

        $settlement->setRelation('card_payments', collect([
            \Modules\Petro\Entities\SettlementCardPayment::findOrFail($firstCard->id),
            \Modules\Petro\Entities\SettlementCardPayment::findOrFail($secondCard->id),
        ]));

        $method->invoke($controller, $settlement, $this->businessId);

        $this->assertSame(
            1,
            DB::table('account_transactions')
                ->where('account_id', $cardAccountId)
                ->whereDate('operation_date', $settlement->transaction_date)
                ->where('amount', 130)
                ->count(),
            'Card account book must show one row for duplicate settlement_card_payments with the same pump_payment_id.'
        );
    }

    private function seedFinalizedSettlement(): int
    {
        $locationId = (int) DB::table('business_locations')
            ->where('business_id', $this->businessId)
            ->value('id');
        if (! $locationId) {
            $this->markTestSkipped("No business_locations for business {$this->businessId}.");
        }

        return DB::table('settlements')->insertGetId([
            'settlement_no'      => 'PDST-IS1313-FIN-' . uniqid(),
            'business_id'        => $this->businessId,
            'transaction_date'   => now()->toDateString(),
            'finish_date'        => now()->toDateString(),
            'location_id'        => $locationId,
            'pump_operator_id'   => $this->pumpOperatorId,
            'bulk_store_product' => 0,
            'work_shift'         => json_encode([]),
            'note'               => null,
            'total_amount'       => '1000',
            'status'             => 0,
            'is_edit'            => 0,
            'created_at'         => now(),
            'updated_at'         => now(),
        ]);
    }

    private function seedCardAccount(): int
    {
        return (int) DB::table('accounts')->insertGetId([
            'name'        => 'Test Card Account IS1313 ' . uniqid(),
            'business_id' => $this->businessId,
            'created_by'  => $this->userId,
            'location_id' => 'all',
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);
    }

    private function seedRequestSession(): void
    {
        /** @var Store $session */
        $session = app('session.store');
        $session->put('business.id', $this->businessId);
        $session->put('user.id', $this->userId);
        request()->setLaravelSession($session);
    }
}
