<?php

namespace Modules\Customers\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Customers-owned activity/audit wrapper.
 */
class CustomerActivity extends Model
{
    protected $table = 'customer_activities';

    protected $guarded = ['id'];

    protected $casts = [
        'business_id' => 'integer',
        'customer_id' => 'integer',
        'created_by' => 'integer',
        'properties' => 'array',
    ];

    public function scopeForBusiness($query, $businessId)
    {
        return $query->where('business_id', $businessId);
    }

    public function scopeForCustomer($query, $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }
}
