<?php

namespace Modules\AirlineTicketingNew\Entities;

use Modules\AirlineTicketingNew\Entities\Concerns\HasAirlineTicketingScope;

class PassengerVisa extends BaseAirlineTicketingModel
{
    use HasAirlineTicketingScope;

    protected $table = 'atn_passenger_visas';

    protected $fillable = [
        'business_id',
        'business_location_id',
        'store_id',
        'passenger_id',
        'country_code',
        'visa_type',
        'visa_number',
        'issued_date',
        'expiry_date',
        'entries_allowed',
        'status',
        'file_path',
        'notes',
        'is_active',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'issued_date' => 'date',
        'expiry_date' => 'date',
        'is_active' => 'boolean'
    ];
}
