<?php
namespace Modules\RestaurantNew\Entities;

class DiningTable extends RestnewModel
{
    protected $table = 'restnew_tables';
    protected $casts = [
        'is_active' => 'boolean',
    ];

public function floor() { return $this->belongsTo(Floor::class, 'floor_id'); }
public function orders() { return $this->hasMany(Order::class, 'table_id'); }
}
