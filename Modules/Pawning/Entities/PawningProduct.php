<?php

namespace Modules\Pawning\Entities;

use Illuminate\Database\Eloquent\Model;

class PawningProduct extends Model
{
    protected $table = 'pawning_products';
    protected $guarded = ['id'];

    public function collateralType()
    {
        return $this->belongsTo(CollateralType::class, 'collateral_type_id');
    }
}
