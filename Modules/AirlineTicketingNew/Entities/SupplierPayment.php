<?php

namespace Modules\AirlineTicketingNew\Entities;

use Modules\AirlineTicketingNew\Entities\Concerns\HasAirlineTicketingScope;

class SupplierPayment extends BaseAirlineTicketingModel
{
    use HasAirlineTicketingScope;

    protected $table = 'atn_supplier_payments';

    protected $fillable = [
        'business_id','business_location_id','store_id','payment_no','supplier_id','settlement_id',
        'payment_date','payment_method','payment_account','reference_no','currency_code','exchange_rate',
        'amount','base_amount','status','remarks','approved_by','paid_by','created_by','updated_by'
    ];

    protected $casts = [
        'payment_date' => 'date',
        'exchange_rate' => 'decimal:8',
        'amount' => 'decimal:4',
        'base_amount' => 'decimal:4',
    ];
}
