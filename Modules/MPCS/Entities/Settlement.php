<?php

namespace Modules\MPCS\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * MA-002: MPCS-owned read model for the SHARED `settlements` table.
 *
 * Same rationale as Modules\MPCS\Entities\Pump: the DATA is shared with
 * the petro modules and must stay shared, but MPCS should not need the
 * Petro module present in order to run. MPCS previously imported
 * Modules\Petro\Entities\Settlement.
 *
 * Read-only by contract. MPCS only queries this table (joins and
 * aggregates inside the F-form controllers). No LogsActivity and no
 * relationships, so this model cannot cause side effects on a table
 * another module owns for writes.
 */
class Settlement extends Model
{
    protected $table = 'settlements';

    protected $guarded = ['id'];

    /*
     * MA-002: mirrors Modules\Petro\Entities\Settlement.
     *
     * MPCS does not currently read work_shift, so this has no effect today.
     * It is included so the two copies of this model stay identical - the
     * drift between duplicated models is precisely what caused the
     * ContactLedger, Journal and FixedAsset problems found elsewhere in this
     * project.
     */
    protected $casts = [
        'work_shift' => 'array',
    ];
}
