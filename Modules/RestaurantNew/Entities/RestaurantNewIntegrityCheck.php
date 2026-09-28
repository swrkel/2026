<?php

namespace Modules\RestaurantNew\Entities;

use Illuminate\Database\Eloquent\Model;

class RestaurantNewIntegrityCheck extends Model
{
    protected $table = 'restaurant_new_integrity_checks';
    protected $guarded = ['id'];
    protected $casts = ['details' => 'array', 'checked_at' => 'datetime'];
}
