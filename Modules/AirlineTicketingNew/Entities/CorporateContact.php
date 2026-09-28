<?php

namespace Modules\AirlineTicketingNew\Entities;

use Modules\AirlineTicketingNew\Entities\Concerns\HasAirlineTicketingScope;

class CorporateContact extends BaseAirlineTicketingModel
{
    use HasAirlineTicketingScope;

    protected $table = 'atn_corporate_contacts';

    protected $fillable = [
        'business_id',
        'business_location_id',
        'store_id',
        'corporate_customer_id',
        'name',
        'designation',
        'department',
        'email',
        'phone',
        'alternate_phone',
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
