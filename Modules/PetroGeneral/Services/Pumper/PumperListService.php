<?php

namespace Modules\PetroGeneral\Services\Pumper;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Pumper management list data.
 *
 * MA-002: guarded against missing tables and an unresolved business.
 *
 * ---------------------------------------------------------------------------
 * THE REPORTED FAULT
 *   /petro-general/pumper-management returned HTTP 500 on tenant cool2:
 *
 *       SQLSTATE[42S02]: Base table or view not found: 1146
 *       Table 'nivasa_cool2.petro_settings' doesn't exist
 *       (SQL: select * from `petro_settings` where `business_id` = 0 limit 1)
 *
 * TWO SEPARATE PROBLEMS IN THAT ONE LINE
 *
 * 1. `petro_settings` DOES NOT EXIST ON ANY TENANT.
 *    Not on cool2, and not on ishadi either - the fully provisioned one. I
 *    checked the schema: there is no such table anywhere. The name appears
 *    exactly ONCE in this module, in the query that was here. Everywhere else
 *    "petro_settings" is a VIEW DIRECTORY name -
 *    view('petrogeneral::petro_settings.index') - so the table name looks to
 *    have been inferred from the view folder rather than from the schema.
 *
 *    This screen would therefore have failed on EVERY tenant, not just cool2.
 *
 *    And the value was never used: pumper_management/tabs/settings.blade.php
 *    is a placeholder that reads no fields from $settings at all. The real
 *    settings editor is elsewhere - PumperSettingsController delegates to
 *    PumpOperatorController::dashboard_settings().
 *
 *    So the query was fetching an imaginary table for a value nobody reads.
 *    I have NOT deleted $settings from the controller, because a view could
 *    start using it; it now resolves to null instead of throwing.
 *
 * 2. business_id = 0.
 *    The controller reads (int) session('business.id'), and 0 means the
 *    session had no business. That is worth guarding on its own: querying
 *    with business_id 0 can only ever return other tenants' unscoped rows or
 *    nothing, and doing it silently is worse than returning empty. Every
 *    method now returns an empty result rather than running the query.
 *
 * ---------------------------------------------------------------------------
 * WHY Schema::hasTable ON THE OTHERS TOO
 *   pump_operators, pump_operator_assignments and petro_daily_shifts DO exist
 *   on a provisioned tenant. But cool2 clearly is not fully provisioned, and a
 *   management screen returning a 500 is a poor way to discover that. The
 *   guard is the same pattern already used elsewhere in these modules
 *   (SchemaCapabilityCache::hasTable in PetroDirect and PetroPD).
 *
 *   The screen now renders with empty lists on an unprovisioned tenant, which
 *   is honest, instead of a Symfony exception page.
 */
class PumperListService
{
    /**
     * A tenant is only queryable if we know which business we are looking at
     * and the table has actually been created.
     */
    private function canQuery(string $table, int $businessId): bool
    {
        return $businessId > 0 && Schema::hasTable($table);
    }

    public function getPumpers(int $businessId)
    {
        if (! $this->canQuery('pump_operators', $businessId)) {
            return collect();
        }

        return DB::table('pump_operators')->where('business_id', $businessId)->latest('id')->limit(50)->get();
    }

    public function getAssignments(int $businessId)
    {
        if (! $this->canQuery('pump_operator_assignments', $businessId)) {
            return collect();
        }

        return DB::table('pump_operator_assignments')->where('business_id', $businessId)->latest('id')->limit(50)->get();
    }

    public function getRecentShifts(int $businessId)
    {
        if (! $this->canQuery('petro_daily_shifts', $businessId)) {
            return collect();
        }

        return DB::table('petro_daily_shifts')->where('business_id', $businessId)->latest('id')->limit(50)->get();
    }

    /**
     * `petro_settings` does not exist on any tenant - see the note at the top
     * of this class. The guard means this returns null rather than throwing,
     * and it will start working by itself if that table is ever created.
     */
    public function getSettings(int $businessId)
    {
        if (! $this->canQuery('petro_settings', $businessId)) {
            return null;
        }

        return DB::table('petro_settings')->where('business_id', $businessId)->first();
    }
}
