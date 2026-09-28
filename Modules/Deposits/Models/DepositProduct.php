<?php

namespace Modules\Deposits\Models;

use Illuminate\Database\Eloquent\Model;

class DepositProduct extends Model
{
    protected $table = 'deposit_products';
    protected $guarded = ['id'];

    protected $casts = [
        'premature_closure_allowed' => 'boolean',
        'require_nominee' => 'boolean',
        'require_beneficiary' => 'boolean',
    ];

    public function accounts()
    {
        return $this->hasMany(DepositAccount::class, 'deposit_product_id');
    }
}
