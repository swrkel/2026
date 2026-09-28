<?php

namespace Modules\Loan\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LoanCustomer extends Model
{
    use SoftDeletes;

    protected $table = 'loan_customers';

    protected $guarded = [];

    protected $casts = [
        'date_of_birth' => 'date',
        'monthly_income' => 'decimal:4',
    ];

    public function scopeForBusiness($query, $business_id)
    {
        return $query->where('business_id', $business_id);
    }

    public function applications()
    {
        return $this->hasMany(LoanApplication::class, 'loan_customer_id');
    }

    public function loans()
    {
        return $this->hasMany(Loan::class, 'loan_customer_id');
    }

    public function getDisplayNameAttribute()
    {
        $name = trim(($this->first_name ?? '') . ' ' . ($this->last_name ?? ''));
        return $name ?: ($this->name ?? 'Loan Customer');
    }

    public function imageUrl($field)
    {
        if (empty($this->{$field})) {
            return null;
        }

        return asset($this->{$field});
    }
}
