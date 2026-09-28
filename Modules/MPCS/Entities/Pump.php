<?php

namespace Modules\MPCS\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * MA-002: MPCS-owned read model for the SHARED `pumps` table.
 *
 * WHY THIS EXISTS
 * ---------------
 * Pumps are common data. Every petro-related module reads the same
 * `pumps` rows, and that must not change. What this class removes is the
 * CODE dependency: MPCS previously imported Modules\Petro\Entities\Pump,
 * so MPCS could not be deployed or maintained without the Petro module.
 *
 * This model maps to the same table, so the DATA stays shared while the
 * MODULE stays standalone.
 *
 * READ-ONLY BY CONTRACT
 * ---------------------
 * MPCS only ever queries pumps (F20/F21/F21C/F22 forms and MPCSController
 * use leftJoin / where / value). Writes remain the responsibility of the
 * owning petro module.
 *
 * Two deliberate omissions, both to avoid side effects on a shared table:
 *   - No LogsActivity. Activity logging belongs to the writing module.
 *     Adding it here would risk duplicate or confusing audit entries.
 *   - No relationships. MPCS joins explicitly in its queries; adding
 *     relations would pull other modules' models back in.
 *
 * If MPCS ever needs to WRITE to pumps, do not add saving here - route it
 * through the owning module's service instead.
 */
class Pump extends Model
{
    protected $table = 'pumps';

    protected $guarded = ['id'];

    public $timestamps = false;
}
