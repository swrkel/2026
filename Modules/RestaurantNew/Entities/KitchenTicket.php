<?php
namespace Modules\RestaurantNew\Entities;

class KitchenTicket extends RestnewModel
{
    protected $table = 'restnew_kitchen_tickets';
    protected $casts = [
        'printed_at' => 'datetime',
        'accepted_at' => 'datetime',
        'started_at' => 'datetime',
        'ready_at' => 'datetime',
    ];

public function order() { return $this->belongsTo(Order::class, 'order_id'); }
public function items() { return $this->hasMany(KitchenTicketItem::class, 'ticket_id'); }
public function station() { return $this->belongsTo(KitchenStation::class, 'station_id'); }
}
