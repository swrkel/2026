<?php

namespace Modules\RestaurantNew\Entities;

use Illuminate\Database\Eloquent\Model;

class RestaurantNewBranchDistribution extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['distribution_date'=>'date'];
}
