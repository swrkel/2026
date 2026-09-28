<?php

namespace Modules\RestaurantNew\Entities;

use Illuminate\Database\Eloquent\Model;

class RestaurantNewKitchenRouteRule extends Model
{
    protected $table = 'resnew_kitchen_route_rules';

    protected $fillable = [
        'business_id','location_id','menu_category_id','menu_item_id','kitchen_section_id',
        'order_type','priority','is_active','created_by','updated_by'
    ];

    protected $casts = ['is_active' => 'boolean'];
}
