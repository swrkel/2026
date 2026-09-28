<?php

namespace Modules\Poultry\Entities\Shared;

/**
 * The ERP's shared contacts table.
 *
 * Hatcheries and feed mills are suppliers; egg buyers, processors and live
 * bird traders are customers. The existing type enum already covers both
 * (supplier, customer, both, lead, contact), so this module introduces no new
 * contact type and creates no parallel party master.
 */
class Contact extends SharedModel
{
    protected $sharedTableKey = 'contacts';
    protected $table = 'contacts';

    public function scopeSuppliers($query)
    {
        return $query->whereIn('type', ['supplier', 'both']);
    }

    public function scopeCustomers($query)
    {
        return $query->whereIn('type', ['customer', 'both']);
    }

    public function scopeNotDeleted($query)
    {
        return $query->whereNull('deleted_at');
    }

    /** id => name list for a select box. */
    public static function dropdown($type = 'supplier', $businessId = null)
    {
        $query = static::query()->forBusiness($businessId)->notDeleted();

        $type === 'customer' ? $query->customers() : $query->suppliers();

        return $query->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();
    }

    public function getDisplayNameAttribute()
    {
        return trim(($this->supplier_business_name ? $this->supplier_business_name.' - ' : '').$this->name);
    }
}
