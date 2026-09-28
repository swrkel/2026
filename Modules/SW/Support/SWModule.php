<?php

namespace Modules\SW\Support;

use Illuminate\Support\Facades\Schema;

/**
 * 8042: is the SW Module available to this business?
 *
 * The SW Shift No field appears on the expense, customer payment and cash
 * deposit screens ONLY when the module is enabled. On an install without it the
 * field would be a dropdown of nothing, asking for a number that means nothing.
 *
 * Two conditions, both required:
 *   - the module is registered and enabled
 *   - its tables exist on this tenant
 *
 * The second matters because a module can be enabled before its migration has
 * run. Checking only the status would leave the field querying a missing table.
 */
class SWModule
{
    public static function isEnabled(): bool
    {
        static $enabled = null;

        if ($enabled !== null) {
            return $enabled;
        }

        try {
            if (! Schema::hasTable('sw_shifts')) {
                return $enabled = false;
            }

            // nwidart's Module facade is not always bound; fall back to the
            // statuses file rather than assuming either is present.
            if (class_exists(\Nwidart\Modules\Facades\Module::class)) {
                $module = \Nwidart\Modules\Facades\Module::find('SW');
                if ($module) {
                    return $enabled = (bool) $module->isEnabled();
                }
            }

            $statuses = base_path('modules_statuses.json');
            if (is_file($statuses)) {
                $decoded = json_decode((string) file_get_contents($statuses), true);
                return $enabled = ! empty($decoded['SW']);
            }

            return $enabled = false;
        } catch (\Throwable $e) {
            // A screen must never break because this check failed.
            return $enabled = false;
        }
    }

    /** Open shifts for a location - the only ones that accept new entries. */
    public static function openShifts(int $businessId, int $locationId)
    {
        if (! self::isEnabled() || $businessId <= 0 || $locationId <= 0) {
            return collect();
        }

        try {
            return \Modules\SW\Entities\Shift::openAt($businessId, $locationId)->get();
        } catch (\Throwable $e) {
            return collect();
        }
    }
}
