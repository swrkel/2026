<?php
namespace Modules\AirlineTicketingNew\Entities;

class HotelRoomType extends BaseAirlineTicketingModel
{
    protected $table = 'atn_hotel_room_types';
    protected $guarded = ['id'];
    protected $casts = [
        'base_cost' => 'decimal:4',
        'base_sale' => 'decimal:4',
        'is_active' => 'boolean',
    ];
}
