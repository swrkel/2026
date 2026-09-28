<?php

namespace Modules\AirlineTicketingNew\Entities;

use Modules\AirlineTicketingNew\Entities\Concerns\HasAirlineTicketingScope;

class HotelReservation extends BaseAirlineTicketingModel
{
    use HasAirlineTicketingScope;

    protected $table = 'atn_hotel_reservations';

    protected $fillable = [
        'business_id','business_location_id','store_id','reservation_no','hotel_id','passenger_id',
        'check_in_date','check_out_date','room_type','room_count','guest_count','meal_plan',
        'currency_code','cost_amount','sale_amount','paid_amount','due_amount','status',
        'voucher_no','remarks','created_by','updated_by'
    ];

    protected $casts = [
        'check_in_date' => 'date','check_out_date' => 'date','cost_amount' => 'decimal:4',
        'sale_amount' => 'decimal:4','paid_amount' => 'decimal:4','due_amount' => 'decimal:4',
    ];
}
