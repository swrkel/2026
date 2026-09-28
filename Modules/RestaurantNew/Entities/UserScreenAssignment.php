<?php

namespace Modules\RestaurantNew\Entities;

use App\BusinessLocation;
use App\User;

class UserScreenAssignment extends RestnewModel
{
    protected $table = 'restnew_user_screen_assignments';

    protected $casts = [
        'is_active' => 'boolean',
        'settings_json' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function location()
    {
        return $this->belongsTo(BusinessLocation::class, 'location_id');
    }

    public function station()
    {
        return $this->belongsTo(KitchenStation::class, 'station_id');
    }
}
