<?php

namespace Modules\RestaurantNew\Entities;

use Illuminate\Database\Eloquent\Model;

class RestaurantNewCustomerFeedback extends Model
{
    protected $table = 'restaurant_new_customer_feedback';

    protected $guarded = ['id'];

    protected $casts = [
        'rating_food' => 'integer',
        'rating_service' => 'integer',
        'rating_overall' => 'integer',
        'metadata' => 'array',
    ];
}
