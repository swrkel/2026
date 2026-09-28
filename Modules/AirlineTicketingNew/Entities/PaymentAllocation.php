<?php

namespace Modules\AirlineTicketingNew\Entities;

use Modules\AirlineTicketingNew\Entities\Concerns\HasAirlineTicketingScope;

class PaymentAllocation extends BaseAirlineTicketingModel
{
    use HasAirlineTicketingScope;

    protected $table = 'atn_payment_allocations';

    protected $fillable = [
        'business_id',
        'business_location_id',
        'store_id',
        'payment_id',
        'invoice_id',
        'reservation_id',
        'allocated_amount',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'allocated_amount' => 'decimal:4'
    ];
}
