<?php

namespace Modules\AirlineTicketingNew\Entities;

use Modules\AirlineTicketingNew\Entities\Concerns\HasAirlineTicketingScope;

class PassengerEmergencyContact extends BaseAirlineTicketingModel
{
    use HasAirlineTicketingScope;

    protected $table = 'atn_passenger_emergency_contacts';

    protected $fillable = [
        'business_id',
        'business_location_id',
        'store_id',
        'passenger_id',
        'name',
        'relationship',
        'phone',
        'alternate_phone',
        'email',
        'country_code',
        'is_primary',
        'is_active',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'is_active' => 'boolean'
    ];
}
