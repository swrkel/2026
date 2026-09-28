<?php

namespace Modules\RestaurantNew\Entities;

use Illuminate\Database\Eloquent\Model;

class RestaurantNewCustomerAllergyWarning extends Model
{
    protected $table = 'restaurant_new_customer_allergy_warnings';
    protected $guarded = ['id'];
}
