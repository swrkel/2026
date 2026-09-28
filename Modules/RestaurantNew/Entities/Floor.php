<?php
namespace Modules\RestaurantNew\Entities;

class Floor extends RestnewModel
{
    protected $table = 'restnew_floors';
    protected $casts = [
        'is_active' => 'boolean',
    ];

public function tables() { return $this->hasMany(DiningTable::class, 'floor_id'); }
}
