<?php

namespace Modules\PetroGeneral\Services\Dip;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/*
 * LA/IS - Dip Management (Petro General) list data.
 *
 * THE FAULT
 *   This service queried tank_dip_readings and dip_resets. Neither table exists
 *   anywhere in the schema - there is no migration for either name - so opening
 *   Petro General / Dip Management ended in
 *       SQLSTATE[42S02] Base table or view not found: 1146
 *       Table '...tank_dip_readings' doesn't exist
 *   and the page returned HTTP 500.
 *
 *   The real tables are the ones the working legacy Dip Management screen and
 *   this module's own Entities use:
 *
 *       tank_dip_readings  ->  dip_readings      (Entities/DipReading.php)
 *       dip_resets         ->  dip_resettings    (Entities/DipResetting.php)
 *       tank_dip_charts    ->  unchanged, this one is correct
 *
 *   All three carry a business_id column, so only the names were wrong.
 *
 * WHY THE GUARD
 *   These tables are created by migrations, and tenant databases in this system
 *   are not always fully migrated - the ProductsNew tables were missing on some
 *   tenants for the same reason. A missing table must not take a whole page down
 *   again, so each read returns an empty set and records why.
 */
class DipListService
{
    public function getReadings(int $businessId)
    {
        return $this->read('dip_readings', $businessId);
    }

    public function getDipCharts(int $businessId)
    {
        return $this->read('tank_dip_charts', $businessId);
    }

    public function getResettings(int $businessId)
    {
        return $this->read('dip_resettings', $businessId);
    }

    public function getReports(int $businessId)
    {
        return collect();
    }

    /**
     * Latest 50 rows for the business, or an empty set if the table is absent
     * on this tenant.
     */
    private function read(string $table, int $businessId)
    {
        try {
            if (! Schema::hasTable($table)) {
                Log::warning('Dip Management: table missing on this tenant', [
                    'table' => $table,
                    'business_id' => $businessId,
                ]);

                return collect();
            }

            return DB::table($table)
                ->where('business_id', $businessId)
                ->latest('id')
                ->limit(50)
                ->get();
        } catch (\Throwable $error) {
            Log::warning('Dip Management: list query failed', [
                'table' => $table,
                'business_id' => $businessId,
                'message' => $error->getMessage(),
            ]);

            return collect();
        }
    }
}
