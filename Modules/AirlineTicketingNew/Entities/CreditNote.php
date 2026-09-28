<?php

namespace Modules\AirlineTicketingNew\Entities;

use Modules\AirlineTicketingNew\Entities\Concerns\HasAirlineTicketingScope;

class CreditNote extends BaseAirlineTicketingModel
{
    use HasAirlineTicketingScope;

    protected $table = 'atn_credit_notes';

    protected $fillable = [
        'business_id',
        'business_location_id',
        'store_id',
        'credit_note_no',
        'credit_note_date',
        'invoice_id',
        'refund_id',
        'customer_type',
        'corporate_customer_id',
        'passenger_id',
        'currency_code',
        'amount',
        'reason',
        'status',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'credit_note_date' => 'date',
        'amount' => 'decimal:4'
    ];
}
