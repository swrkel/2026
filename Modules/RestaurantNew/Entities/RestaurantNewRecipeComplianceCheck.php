<?php

namespace Modules\RestaurantNew\Entities;

use Illuminate\Database\Eloquent\Model;

class RestaurantNewRecipeComplianceCheck extends Model
{
    protected $table = 'restaurant_new_recipe_compliance_checks';
    protected $guarded = ['id'];
}
