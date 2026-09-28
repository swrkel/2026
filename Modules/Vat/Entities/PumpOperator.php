<?php

namespace Modules\Vat\Entities;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * MA-002: Vat-owned model for the SHARED `pump_operators` table.
 *
 * Vat previously imported Modules\Petro\Entities\PumpOperator. The DATA stays
 * shared - same table, same rows - but the CODE dependency is gone.
 *
 * READ-ONLY BY CONTRACT. Vat only queries pump operators; verified there are
 * no create, update, save or delete calls through this class anywhere in Vat.
 *
 * ---------------------------------------------------------------------------
 * MA-002 CORRECTION - A REGRESSION I INTRODUCED AND HAVE NOW FIXED
 * ---------------------------------------------------------------------------
 * When this class was first added it declared only $table and $guarded. That
 * was wrong. Petro's model carries a GLOBAL SCOPE:
 *
 *     protected static function booted()
 *     {
 *         static::addGlobalScope('active', function (Builder $builder) {
 *             $builder->where('active', 1);
 *         });
 *     }
 *
 * Without it, this copy returned ALL pump operators including INACTIVE ones.
 * Vat builds operator dropdowns from it -
 * VatSettlementController lines 173 and 219 -
 * so those lists would have started showing operators that Petro's version
 * correctly hides.
 *
 * The scope is reproduced exactly below, so the two models select the same
 * rows again.
 *
 * LogsActivity is still deliberately omitted: Petro's model logs activity,
 * but that belongs to the module that WRITES this table. Adding it to a
 * read-only copy would risk duplicate or misleading audit entries, and Vat
 * never writes here.
 *
 * *** IF PETRO'S PumpOperator GAINS ANOTHER SCOPE OR HOOK, MIRROR IT HERE. ***
 */
class PumpOperator extends Model
{
    protected $table = 'pump_operators';

    protected $guarded = ['id'];

    /**
     * Mirrors Modules\Petro\Entities\PumpOperator::booted() exactly.
     */
    protected static function booted()
    {
        static::addGlobalScope('active', function (Builder $builder) {
            $builder->where('active', 1);
        });
    }
}
