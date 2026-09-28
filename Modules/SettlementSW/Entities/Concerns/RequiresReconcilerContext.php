<?php

namespace Modules\SettlementSW\Entities\Concerns;

use Illuminate\Database\Eloquent\Model;

/**
 * SW_AUDIT_003
 *
 * Protects reconciled settlement payment rows from accidental direct inserts
 * while still allowing the existing legacy controller flows to operate when
 * they intentionally pass through SettlementPaymentReconciler.
 *
 * This trait is intentionally module-local so Settlement SW does not depend on
 * external Petro/PetroPD guards or shared helper traits.
 */
trait RequiresReconcilerContext
{
    protected static function bootRequiresReconcilerContext(): void
    {
        static::creating(function (Model $model): void {
            if (app()->bound('settlementsw.reconciler.active') && app('settlementsw.reconciler.active') === true) {
                return;
            }

            // Allow legacy/manual rows that do not have a source identity. These are
            // created by Add Payment flows and are later reconciled by settlement_no.
            $hasSourceIdentity = collect(['pump_payment_id', 'customer_payment_id', 'transaction_id'])
                ->contains(function (string $key) use ($model): bool {
                    return ! empty($model->{$key});
                });

            if (! $hasSourceIdentity) {
                return;
            }

            throw new \RuntimeException(
                'Settlement SW payment rows with source identity must be written through SettlementPaymentReconciler.'
            );
        });
    }
}
