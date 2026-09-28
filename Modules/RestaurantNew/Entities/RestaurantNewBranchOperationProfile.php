<?php

namespace Modules\RestaurantNew\Entities;

use Illuminate\Database\Eloquent\Model;

class RestaurantNewBranchOperationProfile extends Model
{
    protected $table = 'restaurant_new_branch_operation_profiles';
    protected $guarded = ['id'];
    protected $casts = [
        'is_central_kitchen' => 'boolean',
        'is_commissary' => 'boolean',
        'is_active' => 'boolean',
        'operating_days' => 'array',
        'meta' => 'array',
    ];
}
