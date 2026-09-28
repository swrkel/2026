<?php

namespace Tests\Feature\Petro\Locks;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Petro\Services\SettlementPaymentReconciler;
use Tests\Feature\Petro\PetroTestCase;

/**
 * Lock 2 now guards UPDATE as well as CREATE.
 *
 * Loaded model updates must go through SettlementPaymentReconciler context.
 *
 * @group characterization
 */
class ModelGuardThrowsOnUpdatingOutsideReconcilerTest extends PetroTestCase
{
    use DatabaseTransactions;

    /** @test */
    public function loaded_model_fill_and_save_outside_reconciler_throws(): void
    {
        $popId = $this->seedPumpOperatorPayment(['payment_type' => 'card']);
        $reconciler = app(SettlementPaymentReconciler::class);

        $card = $reconciler->upsertOne($this->businessId, 'TST-' . uniqid(), 'settlement_card_payments', [
            'customer_id' => $this->contactId,
            'amount' => 100,
            'card_type' => 1,
            'pump_payment_id' => $popId,
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/updates are restricted to SettlementPaymentReconciler/');

        $card->amount = 250;
        $card->save();
    }
}
