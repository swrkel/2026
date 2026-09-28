<?php

namespace Modules\BankingInsurance\Entities;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $table = 'banking_insurance_products';
    protected $guarded = ['id'];

    public function scopeForBusiness($query, $business_id)
    {
        return $query->where('business_id', $business_id);
    }

    public function policies()
    {
        return $this->hasMany(Policy::class, 'product_id');
    }
}
