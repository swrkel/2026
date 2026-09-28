<?php

namespace Modules\AirlineTicketingNew\Entities;

use Modules\AirlineTicketingNew\Entities\Concerns\HasAirlineTicketingScope;

class Ticket extends BaseAirlineTicketingModel
{
    use HasAirlineTicketingScope;

    protected $table = 'atn_tickets';

    protected $fillable = [
        'business_id',
        'business_location_id',
        'store_id',
        'ticket_no',
        'reservation_id',
        'reservation_passenger_id',
        'passenger_id',
        'airline_id',
        'supplier_id',
        'issue_date',
        'ticketing_agent_id',
        'currency_code',
        'exchange_rate',
        'base_fare',
        'tax_total',
        'service_fee_total',
        'discount_total',
        'grand_total',
        'status',
        'ticket_type',
        'original_ticket_id',
        'remarks',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'issue_date' => 'date',
        'exchange_rate' => 'decimal:8',
        'base_fare' => 'decimal:4',
        'tax_total' => 'decimal:4',
        'service_fee_total' => 'decimal:4',
        'discount_total' => 'decimal:4',
        'grand_total' => 'decimal:4'
    ];
}
