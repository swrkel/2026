<?php

namespace Modules\BankingInsurance\Entities;

use Illuminate\Database\Eloquent\Model;

class Policy extends Model
{
    protected $table = 'banking_insurance_policies';
    protected $guarded = ['id'];
    protected $dates = ['start_date', 'end_date'];

    public function scopeForBusiness($query, $business_id)
    {
        return $query->where('business_id', $business_id);
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function premiums()
    {
        return $this->hasMany(Premium::class, 'policy_id');
    }

    public function claims()
    {
        return $this->hasMany(Claim::class, 'policy_id');
    }

    public function getPaidPremiumAttribute()
    {
        return (float) $this->premiums()->sum('amount');
    }
}
