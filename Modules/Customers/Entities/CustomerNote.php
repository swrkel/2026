<?php

namespace Modules\Customers\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Customers-owned note wrapper.
 *
 * This model is intentionally kept inside Modules/Customers so future customer
 * notes do not depend on generic/legacy Contact module note classes.
 */
class CustomerNote extends Model
{
    use SoftDeletes;

    protected $table = 'customer_notes';

    protected $guarded = ['id'];

    protected $casts = [
        'business_id' => 'integer',
        'customer_id' => 'integer',
        'created_by' => 'integer',
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
