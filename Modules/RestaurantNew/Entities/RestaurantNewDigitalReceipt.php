<?php

namespace Modules\RestaurantNew\Entities;

use Illuminate\Database\Eloquent\Model;

class RestaurantNewDigitalReceipt extends Model
{
    protected $table = 'restaurant_new_digital_receipts';

    protected $guarded = ['id'];

    protected $casts = [
        'sent_at' => 'datetime',
        'payload' => 'array',
    ];
}
