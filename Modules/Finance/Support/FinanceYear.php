<?php

namespace Modules\Finance\Support;

use Modules\Finance\Services\YearClosureService;

/**
 * A short way for other modules to ask whether a date is allowed.
 *
 *     if ($reason = FinanceYear::rejectionReason($businessId, $date)) {
 *         return back()->withErrors($reason);
 *     }
 *
 * Every module asks Finance. None keeps its own closing date, because two
 * copies of a rule are two rules, and they will disagree.
 *
 * Every method is guarded: if Finance is absent or its migration has not run,
 * dates are allowed rather than everything being refused. A missing rule should
 * not stop work.
 */
class FinanceYear
{
    protected static function service(): ?YearClosureService
    {
        try {
            return app(YearClosureService::class);
        } catch (\Throwable $e) {
            return null;
        }
    }

    public static function rejectionReason(int $businessId, $date): ?string
    {
        $service = self::service();

        return $service ? $service->rejectionReason($businessId, $date) : null;
    }

    public static function isDateAllowed(int $businessId, $date): bool
    {
        return self::rejectionReason($businessId, $date) === null;
    }

    /** For a date input's min attribute, or null if unconstrained. */
    public static function earliestDate(int $businessId): ?string
    {
        $service = self::service();
        $date = $service ? $service->earliestAllowedDate($businessId) : null;

        return $date ? $date->toDateString() : null;
    }

    public static function closedUpto(int $businessId): ?string
    {
        $service = self::service();
        $date = $service ? $service->closedUpto($businessId) : null;

        return $date ? $date->toDateString() : null;
    }
}
