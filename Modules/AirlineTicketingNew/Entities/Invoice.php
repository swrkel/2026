<?php

namespace Modules\AirlineTicketingNew\Entities;

use Modules\AirlineTicketingNew\Entities\Concerns\HasAirlineTicketingScope;

class Invoice extends BaseAirlineTicketingModel
{
    use HasAirlineTicketingScope;

    protected $table = 'atn_invoices';

    protected $fillable = [
        'business_id',
        'business_location_id',
        'store_id',
        'invoice_no',
        'invoice_date',
        'reservation_id',
        'ticket_id',
        'customer_type',
        'corporate_customer_id',
        'passenger_id',
        'currency_code',
        'exchange_rate',
        'subtotal',
        'tax_total',
        'service_fee_total',
        'discount_total',
        'grand_total',
        'paid_total',
        'due_total',
        'status',
        'remarks',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'exchange_rate' => 'decimal:8',
        'subtotal' => 'decimal:4',
        'tax_total' => 'decimal:4',
        'service_fee_total' => 'decimal:4',
        'discount_total' => 'decimal:4',
        'grand_total' => 'decimal:4',
        'paid_total' => 'decimal:4',
        'due_total' => 'decimal:4'
    ];
}
