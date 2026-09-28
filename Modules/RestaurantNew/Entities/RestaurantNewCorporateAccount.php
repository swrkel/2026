<?php

namespace Modules\RestaurantNew\Entities;

use Illuminate\Database\Eloquent\Model;

class RestaurantNewCorporateAccount extends Model
{
    protected $table = 'restaurant_new_corporate_accounts';
    protected $guarded = ['id'];

    public function scopeForBusiness($query, int $businessId)
    {
        return $query->where('business_id', $businessId);
    }

    public function invoices()
    {
        return $this->hasMany(RestaurantNewCorporateInvoice::class, 'corporate_account_id');
    }
}
