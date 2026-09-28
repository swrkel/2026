<?php

namespace Modules\AirlineTicketingNew\Entities;

use Modules\AirlineTicketingNew\Entities\Concerns\HasAirlineTicketingScope;

class TransferBooking extends BaseAirlineTicketingModel
{
    use HasAirlineTicketingScope;

    protected $table = 'atn_transfer_bookings';

    protected $fillable = [
        'business_id','business_location_id','store_id','booking_no','passenger_id','vehicle_id',
        'transfer_type','pickup_location','drop_location','pickup_at','passenger_count',
        'currency_code','cost_amount','sale_amount','paid_amount','due_amount','status',
        'driver_notes','remarks','created_by','updated_by'
    ];

    protected $casts = [
        'pickup_at' => 'datetime','cost_amount' => 'decimal:4','sale_amount' => 'decimal:4',
        'paid_amount' => 'decimal:4','due_amount' => 'decimal:4',
    ];
}
