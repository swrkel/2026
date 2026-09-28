<?php

namespace Modules\RestaurantNew\Entities;

use Illuminate\Database\Eloquent\Model;

class RestaurantNewKitchenTicket extends Model
{
    protected $table = 'restaurant_new_kitchen_tickets';

    protected $fillable = [
        'business_id', 'business_location_id', 'order_id', 'ticket_no', 'kitchen_section_id',
        'ticket_type', 'status', 'printed_at', 'started_at', 'completed_at', 'cancelled_at',
        'cancel_reason', 'created_by', 'updated_by'
    ];

    protected $casts = [
        'printed_at' => 'datetime', 'started_at' => 'datetime', 'completed_at' => 'datetime', 'cancelled_at' => 'datetime'
    ];

    public function lines()
    {
        return $this->hasMany(RestaurantNewKitchenTicketLine::class, 'kitchen_ticket_id');
    }
}
