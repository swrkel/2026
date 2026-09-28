<?php
namespace Modules\AirlineTicketingNew\Entities;

class AncillaryBooking extends BaseAirlineTicketingModel
{
    protected $table = 'atn_ancillary_bookings';
    protected $guarded = ['id'];
    protected $casts = [
        'service_date' => 'date',
        'quantity' => 'decimal:3',
        'unit_price' => 'decimal:4',
        'total_amount' => 'decimal:4',
    ];
}
