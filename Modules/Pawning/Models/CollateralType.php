<?php

namespace Modules\Pawning\Models;

use Illuminate\Database\Eloquent\Model;

class CollateralType extends Model
{
    protected $table = 'pawning_collateral_types';
    protected $guarded = ['id'];
}
