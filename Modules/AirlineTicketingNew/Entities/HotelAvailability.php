<?php
namespace Modules\AirlineTicketingNew\Entities;

class HotelAvailability extends BaseAirlineTicketingModel
{
    protected $table = 'atn_hotel_availability';
    protected $guarded = ['id'];
    protected $casts = [
        'availability_date' => 'date',
        'cost_rate' => 'decimal:4',
        'sale_rate' => 'decimal:4',
    ];
}
