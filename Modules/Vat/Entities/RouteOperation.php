<?php

namespace Modules\Vat\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * MA-002: Vat-owned model for the SHARED `route_operations` table.
 *
 * Vat previously imported Modules\Fleet\Entities\RouteOperation, which meant Vat could not run
 * without that module installed. The DATA stays shared - this maps to
 * the same table and the same rows - but the CODE dependency is gone.
 *
 * READ-ONLY BY CONTRACT. Vat only queries route operations for the
 * Fleet VAT invoice screens.
 * */
class RouteOperation extends Model
{
    protected $table = 'route_operations';

    protected $guarded = ['id'];
}
