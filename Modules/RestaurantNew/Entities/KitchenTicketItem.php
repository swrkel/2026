<?php
namespace Modules\RestaurantNew\Entities;

class KitchenTicketItem extends RestnewModel
{
    protected $table = 'restnew_kitchen_ticket_items';
    protected $casts = [
        'started_at' => 'datetime',
        'ready_at' => 'datetime',
    ];
}
