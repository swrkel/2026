<?php
namespace Modules\AirlineTicketingNew\Entities;

class TicketExchange extends BaseAirlineTicketingModel
{
    protected $table = 'atn_ticket_exchanges';
    protected $guarded = ['id'];
    protected $casts = [
        'exchange_date' => 'date',
        'original_value' => 'decimal:4',
        'new_value' => 'decimal:4',
        'exchange_difference' => 'decimal:4',
    ];
}
