<?php

namespace Modules\MPCS\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * MA-002: MPCS-owned read model for the SHARED `meter_sales` table.
 *
 * Same rationale as Modules\MPCS\Entities\Pump: the DATA is shared with
 * the petro modules and must stay shared, but MPCS should not need the
 * Petro module present in order to run. MPCS previously imported
 * Modules\Petro\Entities\MeterSale.
 *
 * Read-only by contract. MPCS only queries this table (joins and
 * aggregates inside the F-form controllers). No LogsActivity and no
 * relationships, so this model cannot cause side effects on a table
 * another module owns for writes.
 */
class MeterSale extends Model
{
    protected $table = 'meter_sales';

    protected $guarded = ['id'];
}
