<?php

namespace Modules\AirlineTicketingNew\Entities;

use Modules\AirlineTicketingNew\Entities\Concerns\HasAirlineTicketingScope;

class Reservation extends BaseAirlineTicketingModel
{
    use HasAirlineTicketingScope;

    protected $table = 'atn_reservations';

    protected $fillable = [
        'business_id',
        'business_location_id',
        'store_id',
        'reservation_no',
        'pnr_code',
        'reservation_date',
        'ticketing_deadline',
        'quotation_id',
        'customer_type',
        'corporate_customer_id',
        'passenger_id',
        'agent_id',
        'supplier_id',
        'currency_code',
        'exchange_rate',
        'subtotal',
        'tax_total',
        'service_fee_total',
        'discount_total',
        'grand_total',
        'paid_total',
        'due_total',
        'status',
        'source',
        'remarks',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'reservation_date' => 'date',
        'ticketing_deadline' => 'datetime',
        'exchange_rate' => 'decimal:8',
        'subtotal' => 'decimal:4',
        'tax_total' => 'decimal:4',
        'service_fee_total' => 'decimal:4',
        'discount_total' => 'decimal:4',
        'grand_total' => 'decimal:4',
        'paid_total' => 'decimal:4',
        'due_total' => 'decimal:4'
    ];
}
