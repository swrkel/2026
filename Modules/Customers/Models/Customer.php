<?php

namespace Modules\Customers\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $table = 'contacts';

    protected $guarded = [];

    public function scopeOnlyCustomers($query)
    {
        return $query->where('type', 'customer');
    }

    public function scopeForBusiness($query, $businessId)
    {
        return $query->where('business_id', $businessId);
    }
}
