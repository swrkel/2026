<?php

namespace Modules\Poultry\Entities\Shared;

/**
 * The ERP's shared business_locations table.
 *
 * A farm site maps to a business location, which is what gives poultry stock
 * movements a location the rest of the ERP already understands. Individual
 * houses sit below this, in poultry_houses.
 */
class BusinessLocation extends SharedModel
{
    protected $sharedTableKey = 'business_locations';
    protected $table = 'business_locations';

    public function scopeNotDeleted($query)
    {
        return $query->whereNull('deleted_at');
    }

    public static function dropdown($businessId = null)
    {
        return static::query()
            ->forBusiness($businessId)
            ->notDeleted()
            ->where('is_active', 1)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();
    }
}
