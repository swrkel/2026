<?php

namespace Modules\AirlineTicketingNew\Entities;

use Modules\AirlineTicketingNew\Entities\Concerns\HasAirlineTicketingScope;

class Quotation extends BaseAirlineTicketingModel
{
    use HasAirlineTicketingScope;

    protected $table = 'atn_quotations';

    protected $fillable = [
        'business_id',
        'business_location_id',
        'store_id',
        'quotation_no',
        'quotation_date',
        'valid_until',
        'customer_type',
        'corporate_customer_id',
        'passenger_id',
        'agent_id',
        'currency_code',
        'exchange_rate',
        'subtotal',
        'tax_total',
        'service_fee_total',
        'discount_total',
        'grand_total',
        'status',
        'remarks',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'quotation_date' => 'date',
        'valid_until' => 'date',
        'exchange_rate' => 'decimal:8',
        'subtotal' => 'decimal:4',
        'tax_total' => 'decimal:4',
        'service_fee_total' => 'decimal:4',
        'discount_total' => 'decimal:4',
        'grand_total' => 'decimal:4'
    ];
}
