<?php

namespace Modules\AirlineTicketingNew\Entities;

use Modules\AirlineTicketingNew\Entities\Concerns\HasAirlineTicketingScope;

class PassengerDocument extends BaseAirlineTicketingModel
{
    use HasAirlineTicketingScope;

    protected $table = 'atn_passenger_documents';

    protected $fillable = [
        'business_id',
        'business_location_id',
        'store_id',
        'passenger_id',
        'document_type',
        'document_number',
        'issuing_country_code',
        'issued_date',
        'expiry_date',
        'place_of_issue',
        'file_path',
        'is_primary',
        'is_verified',
        'verified_by',
        'verified_at',
        'notes',
        'is_active',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'issued_date' => 'date',
        'expiry_date' => 'date',
        'is_primary' => 'boolean',
        'is_verified' => 'boolean',
        'verified_at' => 'datetime',
        'is_active' => 'boolean'
    ];
}
