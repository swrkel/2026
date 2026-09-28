<?php

namespace Modules\Vat\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * MA-002: Vat-owned model for the SHARED `pumps` table.
 *
 * Vat previously imported Modules\Petro\Entities\Pump, which meant Vat could not run
 * without that module installed. The DATA stays shared - this maps to
 * the same table and the same rows - but the CODE dependency is gone.
 *
 * READ-ONLY BY CONTRACT. Vat only queries pumps (where/whereIn in the
 * settlement and invoice screens). No LogsActivity and no relationships,
 * so this model cannot cause side effects on a table another module
 * owns for writes.
 * */
class Pump extends Model
{
    protected $table = 'pumps';

    protected $guarded = ['id'];
}
