<?php

namespace Modules\AirlineTicketingNew\Entities;

use Modules\AirlineTicketingNew\Entities\Concerns\HasAirlineTicketingScope;

class PassengerLoyaltyAccount extends BaseAirlineTicketingModel
{
    use HasAirlineTicketingScope;

    protected $table = 'atn_passenger_loyalty_accounts';

    protected $fillable = [
        'business_id',
        'business_location_id',
        'store_id',
        'passenger_id',
        'airline_id',
        'program_name',
        'membership_number',
        'tier_name',
        'points_balance',
        'expiry_date',
        'is_active',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'points_balance' => 'decimal:2',
        'expiry_date' => 'date',
        'is_active' => 'boolean'
    ];
}
