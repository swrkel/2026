<?php

namespace Modules\PetroGeneral\Entities\Concerns;

/**
 * Lock 2 - application-layer write guard.
 *
 * Settlement payment creates and updates must happen inside
 * SettlementPaymentReconciler::upsertOne, reconcileSet, or withBypass.
 */
trait RequiresReconcilerContext
{
    protected static array $reconcilerCallerAllowlist = [];

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
        if (app()->bound('petrogeneral.reconciler.active')) {
            return;
        }

        $caller = self::detectCallerNamespace();
        foreach (self::$reconcilerCallerAllowlist as $allowedPrefix) {
            if ($caller !== null && str_starts_with($caller, $allowedPrefix)) {
                return;
            }
        }

        throw new \RuntimeException(
            static::class . " {$operation} are restricted to SettlementPaymentReconciler."
        );
    }

    /**
     * Walk the call stack and find the first frame that is not framework,
     * guarded model, or reconciler internals.
     */
    private static function detectCallerNamespace(): ?string
    {
        foreach (debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 30) as $frame) {
            $cls = $frame['class'] ?? null;
            if (! $cls) {
                continue;
            }
            if (str_starts_with($cls, 'Illuminate\\')
                || str_starts_with($cls, 'Modules\\PetroGeneral\\Entities\\')
                || str_starts_with($cls, 'Modules\\Vat\\Entities\\')
                || str_starts_with($cls, 'Modules\\PetroGeneral\\Services\\SettlementPaymentReconciler')
            ) {
                continue;
            }
            return $cls;
        }

        return null;
    }
}
