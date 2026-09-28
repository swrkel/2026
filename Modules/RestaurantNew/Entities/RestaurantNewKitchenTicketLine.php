<?php

namespace Modules\RestaurantNew\Entities;

use Illuminate\Database\Eloquent\Model;

class RestaurantNewKitchenTicketLine extends Model
{
    protected $table = 'restaurant_new_kitchen_ticket_lines';

    protected $fillable = [
        'business_id', 'business_location_id', 'kitchen_ticket_id', 'order_line_id', 'menu_item_id',
        'item_name', 'quantity', 'modifiers_text', 'special_instruction', 'status', 'started_at',
        'completed_at', 'cancelled_at', 'cancel_reason', 'created_by', 'updated_by'
    ];

    protected $casts = [
        'quantity' => 'decimal:4', 'started_at' => 'datetime', 'completed_at' => 'datetime', 'cancelled_at' => 'datetime'
    ];
}
