<?php

namespace Modules\RestaurantNew\Entities;

class RestaurantNewTable extends RestaurantNewBaseModel
{
    protected $table = 'rn_tables';

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function diningArea()
    {
        return $this->belongsTo(RestaurantNewDiningArea::class, 'dining_area_id');
    }
}
