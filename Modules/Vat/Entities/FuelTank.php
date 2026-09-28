<?php

namespace Modules\Vat\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * MA-002: Vat-owned model for the SHARED `fuel_tanks` table.
 *
 * Vat previously imported Modules\Petro\Entities\FuelTank, which meant Vat could not run
 * without that module installed. The DATA stays shared - this maps to
 * the same table and the same rows - but the CODE dependency is gone.
 *
 * READ-ONLY BY CONTRACT. Vat only queries fuel tanks. LogsActivity is
 * deliberately omitted - audit logging belongs to the writing module,
 * and duplicating it here would produce confusing audit entries.
 * */
class FuelTank extends Model
{
    protected $table = 'fuel_tanks';

    protected $guarded = ['id'];
}
