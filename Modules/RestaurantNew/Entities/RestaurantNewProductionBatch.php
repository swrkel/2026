<?php

namespace Modules\RestaurantNew\Entities;

use Illuminate\Database\Eloquent\Model;

class RestaurantNewProductionBatch extends Model
{
    protected $table = 'restaurant_new_production_batches';
    protected $guarded = ['id'];
}
