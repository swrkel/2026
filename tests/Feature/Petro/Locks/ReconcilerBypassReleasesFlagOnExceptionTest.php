<?php

namespace Tests\Feature\Petro\Locks;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Petro\Services\SettlementPaymentReconciler;
use Tests\Feature\Petro\PetroTestCase;

/**
 * Lock 2 safety: the 'petro.reconciler.active' flag must be cleared even if
 * the inner callback throws — otherwise a leaked flag would let direct
 * ::create() calls slip through in subsequent code paths.
 *
 * @group characterization
 */
class ReconcilerBypassReleasesFlagOnExceptionTest extends PetroTestCase
{
    use DatabaseTransactions;

    /** @test */
    public function withBypass_clears_flag_when_callback_throws(): void
    {
        $this->assertFalse(app()->bound('petro.reconciler.active'));

        try {
            SettlementPaymentReconciler::withBypass(function () {
                throw new \RuntimeException('forced failure');
            });
            $this->fail('Expected RuntimeException.');
        } catch (\RuntimeException $e) {
            // expected
        }

        $this->assertFalse(
            app()->bound('petro.reconciler.active'),
            'petro.reconciler.active must be cleared in finally even when the callback throws.'
        );
    }

    /** @test */
    public function upsert_one_clears_flag_when_inner_throws(): void
    {
        $this->assertFalse(app()->bound('petro.reconciler.active'));

        try {
            // Pass invalid table — Reconciler throws InvalidArgumentException at top of method,
            // BEFORE entering the DB::transaction body. So we use a valid table but a payload
            // that violates a DB constraint (missing required FK) to force the throw inside.
            app(SettlementPaymentReconciler::class)->upsertOne(
                $this->businessId, 'TST-' . uniqid(), 'settlement_card_payments',
                [
                    'customer_id' => 0, // invalid FK
                    'amount' => 100,
                    'card_type' => 1,
                    'pump_payment_id' => 0, // also invalid
                ]
            );
        } catch (\Throwable $e) {
            // expected — FK violation or similar
        }

        $this->assertFalse(
            app()->bound('petro.reconciler.active'),
            'Reconciler must release the flag in its finally block.'
        );
    }
}
