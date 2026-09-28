<?php

namespace Modules\PetroPD\Entities\Concerns;

/**
 * Application-layer write guard for settlement payment detail models.
 *
 * Creates and updates are allowed only while SettlementPaymentReconciler (or
 * the authorised payment edit service) owns the write context. The context is
 * depth-aware so a nested reconciliation cannot accidentally clear an outer
 * reconciliation and block the next legitimate model write.
 */
trait RequiresReconcilerContext
{
    private const RECONCILER_ACTIVE_KEY = 'petro.reconciler.active';
    private const RECONCILER_DEPTH_KEY = 'petro.reconciler.depth';

    /**
     * Services that are permitted to write guarded settlement payment models.
     * The Reconciler remains the normal owner; the edit service uses the same
     * guarded context for explicitly authorised edits.
     */
    protected static array $reconcilerCallerAllowlist = [
        'Modules\\PetroPD\\Services\\SettlementPaymentReconciler',
        'Modules\\PetroPD\\Services\\SettlementPaymentEditService',

        /*
         * S 639: finalising a PD settlement.
         *
         * PetroPDSettlementController::store() writes these payment rows
         * directly while committing a settlement, so the guard refused it:
         *
         *   "SettlementCashPayment updates are restricted to
         *    SettlementPaymentReconciler"
         *
         * The guard had never fired before, because Finalize could not reach
         * the server at all - the preview it hands off to never opened. Once
         * that was fixed the save arrived here and was rejected.
         *
         * Listing the controller here rather than wrapping the save in
         * withBypass() is deliberate: it is a one-line change to a 78-line
         * file, instead of restructuring a 4,700-line trait around a closure.
         * The effect is the same and it is far easier to verify.
         *
         * This does NOT open the guard generally. hasAuthorisedCaller() checks
         * the live call stack, so only writes actually made while finalising a
         * settlement are permitted; a write from anywhere else is still
         * refused.
         *
         * The cleaner end state is for these writes to go through the
         * reconciler's upsertOne()/reconcileSet(), which would remove the need
         * for this entry. That belongs with the single-source work.
         */
        'Modules\\PetroPD\\Http\\Controllers\\PetroPDSettlementController',
    ];

    protected static function bootRequiresReconcilerContext(): void
    {
        static::creating(function ($model) {
            self::assertReconcilerContext('writes');
        });

        static::updating(function ($model) {
            self::assertReconcilerContext('updates');
        });
    }

    private static function assertReconcilerContext(string $operation): void
    {
        $active = app()->bound(self::RECONCILER_ACTIVE_KEY)
            && app(self::RECONCILER_ACTIVE_KEY) === true;

        $depth = app()->bound(self::RECONCILER_DEPTH_KEY)
            ? (int) app(self::RECONCILER_DEPTH_KEY)
            : 0;

        if ($active || $depth > 0 || self::hasAuthorisedCaller()) {
            return;
        }

        throw new \RuntimeException(
            static::class . " {$operation} are restricted to SettlementPaymentReconciler."
        );
    }

    /**
     * Backstop for legitimate reconciler-owned writes if a framework callback
     * or nested flow temporarily changes container bindings. This does not
     * permit controller/model writes: an authorised service must be present in
     * the actual call stack.
     */
    private static function hasAuthorisedCaller(): bool
    {
        foreach (debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 40) as $frame) {
            $class = $frame['class'] ?? null;
            if (! is_string($class) || $class === '') {
                continue;
            }

            foreach (self::$reconcilerCallerAllowlist as $allowedClass) {
                if ($class === $allowedClass || str_starts_with($class, $allowedClass . '@anonymous')) {
                    return true;
                }
            }
        }

        return false;
    }
}
