<?php
namespace Modules\RestaurantNew\Entities;

class Shift extends RestnewModel
{
    protected $table = 'restnew_shifts';
    protected $casts = [
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

public function orders() { return $this->hasMany(Order::class, 'shift_id'); }
public function payments() { return $this->hasMany(Payment::class, 'shift_id'); }
}
