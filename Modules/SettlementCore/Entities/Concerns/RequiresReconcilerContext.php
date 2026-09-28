<?php

namespace Modules\SettlementCore\Entities\Concerns;

use Modules\SettlementCore\Services\AbstractSettlementPaymentReconciler;

/**
 * MA-002: shared "Lock 2" application-layer write guard.
 *
 * Settlement payment creates and updates must happen inside a reconciler
 * that extends AbstractSettlementPaymentReconciler. Any other caller gets
 * a RuntimeException.
 *
 * This replaces the per-module copies of RequiresReconcilerContext for
 * models that have been migrated. It is behaviourally identical to the
 * Petro original with two deliberate improvements:
 *
 *  1. It checks the SHARED flag, so any module's reconciler satisfies it.
 *     The Petro copy checked 'petro.reconciler.active' only, which meant a
 *     different module's reconciler writing these models would throw even
 *     though it is a legitimate reconciler. (Not currently reachable, but
 *     Petro and PetroDirect both map the vat_* tables, so it was a trap
 *     waiting for the first caller.)
 *
 *  2. The caller-namespace skip list is derived from the MODEL'S OWN
 *     namespace instead of hard-coding 'Modules\Vat\Entities\' and
 *     'Modules\Petro\Entities\'. The original had Petro's trait naming Vat
 *     explicitly, which is the coupling this whole change removes.
 *
 * Legacy per-module flags are still honoured through
 * $reconcilerLegacyFlags so a module can migrate its models one at a time.
 */
trait RequiresReconcilerContext
{
    /**
     * Caller class prefixes allowed to write without an active reconciler.
     * Empty by default - populate only with a documented reason.
     *
     * @var array<int, string>
     */
    protected static array $reconcilerCallerAllowlist = [];

    /**
     * Additional container flags accepted besides the shared one. Lets a
     * module keep its historical flag working while models are migrated.
     *
     * @var array<int, string>
     */
    protected static array $reconcilerLegacyFlags = [];

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
        foreach (self::reconcilerFlags() as $flag) {
            if (app()->bound($flag)) {
                return;
            }
        }

        $caller = self::detectCallerNamespace();
        foreach (self::$reconcilerCallerAllowlist as $allowedPrefix) {
            if ($caller !== null && str_starts_with($caller, $allowedPrefix)) {
                return;
            }
        }

        throw new \RuntimeException(
            static::class . " {$operation} are restricted to a settlement payment reconciler."
        );
    }

    /**
     * @return array<int, string>
     */
    private static function reconcilerFlags(): array
    {
        return array_merge(
            [AbstractSettlementPaymentReconciler::SHARED_FLAG],
            static::$reconcilerLegacyFlags
        );
    }

    /**
     * Walk the call stack and find the first frame that is not framework,
     * guarded-model, or reconciler internals.
     */
    private static function detectCallerNamespace(): ?string
    {
        // Skip frames belonging to the model's own entity namespace rather
        // than naming other modules explicitly.
        $ownNamespace = substr(static::class, 0, strrpos(static::class, '\\') + 1);

        foreach (debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 30) as $frame) {
            $cls = $frame['class'] ?? null;
            if (! $cls) {
                continue;
            }

            if (str_starts_with($cls, 'Illuminate\\')
                || str_starts_with($cls, $ownNamespace)
                || str_starts_with($cls, 'Modules\\SettlementCore\\')
                || is_subclass_of($cls, AbstractSettlementPaymentReconciler::class)
            ) {
                continue;
            }

            return $cls;
        }

        return null;
    }
}
