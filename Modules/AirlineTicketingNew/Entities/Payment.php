<?php

namespace Modules\AirlineTicketingNew\Entities;

use Modules\AirlineTicketingNew\Entities\Concerns\HasAirlineTicketingScope;

class Payment extends BaseAirlineTicketingModel
{
    use HasAirlineTicketingScope;

    protected $table = 'atn_payments';

    protected $fillable = [
        'business_id',
        'business_location_id',
        'store_id',
        'payment_no',
        'payment_date',
        'reservation_id',
        'invoice_id',
        'customer_type',
        'corporate_customer_id',
        'passenger_id',
        'payment_method',
        'payment_account',
        'reference_no',
        'currency_code',
        'exchange_rate',
        'amount',
        'base_amount',
        'status',
        'remarks',
        'received_by',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'payment_date' => 'date',
        'exchange_rate' => 'decimal:8',
        'amount' => 'decimal:4',
        'base_amount' => 'decimal:4'
    ];
}
