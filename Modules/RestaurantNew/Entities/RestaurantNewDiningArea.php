<?php

namespace Modules\RestaurantNew\Entities;

class RestaurantNewDiningArea extends RestaurantNewBaseModel
{
    protected $table = 'rn_dining_areas';

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function tables()
    {
        return $this->hasMany(RestaurantNewTable::class, 'dining_area_id');
    }
}
