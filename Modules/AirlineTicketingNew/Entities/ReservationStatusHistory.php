<?php

namespace Modules\AirlineTicketingNew\Entities;

use Modules\AirlineTicketingNew\Entities\Concerns\HasAirlineTicketingScope;

class ReservationStatusHistory extends BaseAirlineTicketingModel
{
    use HasAirlineTicketingScope;

    protected $table = 'atn_reservation_status_history';

    protected $fillable = [
        'business_id',
        'business_location_id',
        'store_id',
        'reservation_id',
        'from_status',
        'to_status',
        'reason',
        'changed_by',
        'changed_at'
    ];

    protected $casts = [
        'changed_at' => 'datetime'
    ];
}
