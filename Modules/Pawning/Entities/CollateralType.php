<?php

namespace Modules\Pawning\Entities;

use Illuminate\Database\Eloquent\Model;

class CollateralType extends Model
{
    protected $table = 'pawning_collateral_types';
    protected $guarded = ['id'];
}
