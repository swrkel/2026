<?php

namespace Modules\AirlineTicketingNew\Entities;

use Modules\AirlineTicketingNew\Entities\Concerns\HasAirlineTicketingScope;

class Refund extends BaseAirlineTicketingModel
{
    use HasAirlineTicketingScope;

    protected $table = 'atn_refunds';

    protected $fillable = [
        'business_id',
        'business_location_id',
        'store_id',
        'refund_no',
        'ticket_id',
        'invoice_id',
        'payment_id',
        'cancellation_id',
        'request_date',
        'approved_date',
        'refund_date',
        'currency_code',
        'gross_amount',
        'cancellation_fee',
        'service_fee',
        'other_deductions',
        'refund_amount',
        'refund_method',
        'reference_no',
        'status',
        'requested_by',
        'approved_by',
        'processed_by',
        'remarks',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'request_date' => 'date',
        'approved_date' => 'date',
        'refund_date' => 'date',
        'gross_amount' => 'decimal:4',
        'cancellation_fee' => 'decimal:4',
        'service_fee' => 'decimal:4',
        'other_deductions' => 'decimal:4',
        'refund_amount' => 'decimal:4'
    ];
}
