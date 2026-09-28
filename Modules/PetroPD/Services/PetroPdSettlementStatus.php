<?php

namespace Modules\PetroPD\Services;

/**
 * PetroPD settlement status helper.
 *
 * Legacy settlement records use numeric status values:
 *   status = 1 : open/draft settlement
 *   status = 0 : closed/finalized settlement
 *
 * Some newer code temporarily treated status=0 as draft. This helper keeps the
 * rule explicit and prevents Payment Summary from locking merely because a
 * shift is closed, while still locking after the actual settlement final save.
 */
class PetroPdSettlementStatus
{
    public static function isOpen($settlement): bool
    {
        if (empty($settlement)) {
            return false;
        }

        return (string) ($settlement->status ?? '') === '1';
    }

    public static function isFinalized($settlement): bool
    {
        if (empty($settlement)) {
            return false;
        }

        $status = strtolower(trim((string) ($settlement->status ?? '')));
        if (in_array($status, ['finalized', 'finalised', 'closed', 'completed', 'complete', 'approved', 'posted'], true)) {
            return true;
        }

        if ($status === '0') {
            return (int) ($settlement->is_edit ?? 0) === 0
                || ! empty($settlement->finish_date)
                || ! empty($settlement->settlement_date);
        }

        return false;
    }
}
