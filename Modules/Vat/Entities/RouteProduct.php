<?php

namespace Modules\Vat\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * MA-002: Vat-owned model for the SHARED `route_products` table.
 *
 * Vat previously imported Modules\Fleet\Entities\RouteProduct, which meant Vat could not run
 * without that module installed. The DATA stays shared - this maps to
 * the same table and the same rows - but the CODE dependency is gone.
 *
 * READ-ONLY BY CONTRACT. Vat only queries route products.
 * */
class RouteProduct extends Model
{
    protected $table = 'route_products';

    protected $guarded = ['id'];
}
