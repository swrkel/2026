<?php

namespace Modules\AirlineTicketingNew\Entities;

use Modules\AirlineTicketingNew\Entities\Concerns\HasAirlineTicketingScope;

class CorporateCustomer extends BaseAirlineTicketingModel
{
    use HasAirlineTicketingScope;

    protected $table = 'atn_corporate_customers';

    protected $fillable = [
        'business_id',
        'business_location_id',
        'store_id',
        'customer_no',
        'company_name',
        'registration_no',
        'tax_no',
        'contact_person',
        'email',
        'phone',
        'alternate_phone',
        'billing_address',
        'city',
        'country_code',
        'credit_limit',
        'payment_terms_days',
        'currency_code',
        'is_active',
        'notes',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'credit_limit' => 'decimal:4',
        'is_active' => 'boolean'
    ];
}
