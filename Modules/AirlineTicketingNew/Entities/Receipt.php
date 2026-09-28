<?php

namespace Modules\AirlineTicketingNew\Entities;

use Modules\AirlineTicketingNew\Entities\Concerns\HasAirlineTicketingScope;

class Receipt extends BaseAirlineTicketingModel
{
    use HasAirlineTicketingScope;

    protected $table = 'atn_receipts';

    protected $fillable = [
        'business_id',
        'business_location_id',
        'store_id',
        'receipt_no',
        'receipt_date',
        'payment_id',
        'invoice_id',
        'reservation_id',
        'currency_code',
        'amount',
        'status',
        'remarks',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'receipt_date' => 'date',
        'amount' => 'decimal:4'
    ];
}
