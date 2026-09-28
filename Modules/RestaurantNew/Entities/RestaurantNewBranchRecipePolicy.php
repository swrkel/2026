<?php

namespace Modules\RestaurantNew\Entities;

use Illuminate\Database\Eloquent\Model;

class RestaurantNewBranchRecipePolicy extends Model
{
    protected $table = 'restaurant_new_branch_recipe_policies';
    protected $guarded = ['id'];
    protected $casts = [
        'allow_local_override' => 'boolean',
        'approval_required' => 'boolean',
        'effective_from' => 'date',
        'meta' => 'array',
    ];
}
