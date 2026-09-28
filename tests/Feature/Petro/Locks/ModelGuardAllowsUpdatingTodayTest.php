<?php

namespace Tests\Feature\Petro\Locks;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Petro\Services\SettlementPaymentReconciler;
use Tests\Feature\Petro\PetroTestCase;

/**
 * Lock 2 is CREATE-ONLY today.
 *
 * `updating` event is intentionally NOT guarded. Legitimate fill+save edit
 * flows (e.g. AddPaymentController.php:384-385) must continue to work until
 * week 1 routes them through SettlementPaymentReconciler::upsertOne().
 *
 * This test locks the create-only contract. If it fails, someone added an
 * `updating` guard before week 1 and broke a legitimate flow.
 *
 * @group characterization
 */
class ModelGuardAllowsUpdatingTodayTest extends PetroTestCase
{
    use DatabaseTransactions;

    /** @test */
    public function loaded_model_fill_and_save_outside_reconciler_does_not_throw(): void
    {
        $popId = $this->seedPumpOperatorPayment(['payment_type' => 'card']);
        $reconciler = app(SettlementPaymentReconciler::class);

        // Create via reconciler (allowed)…
        $card = $reconciler->upsertOne($this->businessId, 'TST-' . uniqid(), 'settlement_card_payments', [
            'customer_id' => $this->contactId, 'amount' => 100, 'card_type' => 1, 'pump_payment_id' => $popId,
        ]);

        // …then update outside any reconciler context. Must NOT throw under create-only guard.
        $card->amount = 250;
        $card->save();

        $this->assertEquals(250, (int) $card->fresh()->amount);
    }
}
