<?php

namespace Modules\RestaurantNew\Entities;

use Illuminate\Database\Eloquent\Model;

class RestaurantNewCustomerProfile extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['favourite_items'=>'array','dietary_preferences'=>'array','allergies'=>'array','last_visit_at'=>'datetime'];
}
