<?php

namespace Modules\RestaurantNew\Entities;

use Illuminate\Database\Eloquent\Model;

class RestaurantNewFeatureSetting extends Model
{
    protected $table = 'restaurant_new_feature_settings';
    protected $guarded = ['id'];
    protected $casts = ['settings'=>'array','allowed_actions'=>'array','old_values'=>'array','new_values'=>'array','is_enabled'=>'boolean','is_allowed'=>'boolean'];
}
