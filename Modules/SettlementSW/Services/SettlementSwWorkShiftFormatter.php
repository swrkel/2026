<?php

namespace Modules\SettlementSW\Services;

use Modules\SettlementSW\Entities\SettlementSwWorkShift;

/**
 * Keeps Settlement SW views free from direct database/model lookups for work-shift labels.
 *
 * This is intentionally small and module-local so future Settlement SW work-shift table
 * migration only needs changes inside the module wrapper/config area.
 */
class SettlementSwWorkShiftFormatter
{
    public static function timingLabel($workShiftId): string
    {
        if (empty($workShiftId)) {
            return '';
        }

        $workShift = SettlementSwWorkShift::find($workShiftId);

        if (empty($workShift)) {
            return '';
        }

        $from = $workShift->shift_form ?? $workShift->shift_from ?? '';
        $to = $workShift->shift_to ?? '';

        return trim($from . ' ' . __('settlementsw::lang.to') . ' ' . $to);
    }
}
