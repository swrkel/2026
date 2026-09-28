<?php

namespace Modules\AirlineTicketingNew\Entities;

use Modules\AirlineTicketingNew\Entities\Concerns\HasAirlineTicketingScope;

class Passenger extends BaseAirlineTicketingModel
{
    use HasAirlineTicketingScope;

    protected $table = 'atn_passengers';

    protected $fillable = [
        'business_id',
        'business_location_id',
        'store_id',
        'passenger_no',
        'title',
        'first_name',
        'middle_name',
        'last_name',
        'gender',
        'date_of_birth',
        'nationality_code',
        'email',
        'phone',
        'alternate_phone',
        'address',
        'city',
        'country_code',
        'corporate_customer_id',
        'is_vip',
        'is_active',
        'notes',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'is_vip' => 'boolean',
        'is_active' => 'boolean'
    ];
}
