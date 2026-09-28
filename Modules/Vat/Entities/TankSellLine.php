<?php

namespace Modules\Vat\Entities;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * MA-002: Vat-owned model for the SHARED `tank_sell_lines` table.
 *
 * Vat previously imported Modules\Petro\Entities\TankSellLine. The DATA
 * stays shared - same table, same rows - but the CODE dependency is gone.
 *
 * NOT READ-ONLY. Unlike the other Vat-owned models added in MA-002, this
 * table IS written to by Vat: VatInvoiceController, VatInvoice2Controller
 * and FleetVatInvoice2Controller each call TankSellLine::create() when an
 * invoice consumes fuel from a tank.
 *
 * Two things are therefore replicated EXACTLY from the Petro model rather
 * than trimmed away:
 *
 *   1. $guarded = ['id'] - identical mass-assignment behaviour, so the
 *      create() calls in those three controllers behave the same.
 *
 *   2. LogsActivity with $logName 'Tank Purchase Line' - the Petro model
 *      logs activity, so rows Vat creates ALREADY produce audit entries
 *      today. Dropping the trait here would silently stop logging Vat's
 *      tank sell lines, which is a behaviour regression, not a cleanup.
 *      The log name is kept identical so existing audit queries and
 *      reports continue to match.
 *
 * The fuel_tanks() relationship from the Petro model is intentionally
 * omitted: Vat never traverses it, and including it would pull
 * Modules\Petro\Entities\FuelTank straight back in. Vat has its own
 * FuelTank read model if it is ever needed.
 *
 * *** IF A COLUMN IS ADDED TO tank_sell_lines, REVIEW BOTH MODELS. ***
 */
class TankSellLine extends Model
{
    use LogsActivity;

    protected $table = 'tank_sell_lines';

    protected $fillable = [];

    protected static $logAttributes = ['*'];

    protected static $logFillable = true;

    protected static $logName = 'Tank Purchase Line';

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['fillable', 'some_other_attribute']);
    }
}
