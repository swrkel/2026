<?php

namespace Modules\RestaurantNew\Entities;

use Illuminate\Database\Eloquent\Model;

class RestaurantNewQrMenu extends Model
{
    protected $table = 'restaurant_new_qr_menus';

    protected $guarded = ['id'];

    protected $casts = [
        'is_active' => 'boolean',
        'allow_self_order' => 'boolean',
        'settings' => 'array',
    ];
}
