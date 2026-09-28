<?php

namespace Modules\AirlineTicketingNew\Entities;

use Modules\AirlineTicketingNew\Entities\Concerns\HasAirlineTicketingScope;

class BspRemittance extends BaseAirlineTicketingModel
{
    use HasAirlineTicketingScope;

    protected $table = 'atn_bsp_remittances';

    protected $fillable = [
        'business_id','business_location_id','store_id','remittance_no','period_from','period_to',
        'remittance_date','currency_code','gross_sales','refunds','commissions','taxes','adjustments',
        'net_payable','paid_amount','due_amount','status','reference_no','remarks','created_by','updated_by'
    ];

    protected $casts = [
        'period_from' => 'date','period_to' => 'date','remittance_date' => 'date',
        'gross_sales' => 'decimal:4','refunds' => 'decimal:4','commissions' => 'decimal:4',
        'taxes' => 'decimal:4','adjustments' => 'decimal:4','net_payable' => 'decimal:4',
        'paid_amount' => 'decimal:4','due_amount' => 'decimal:4',
    ];
}
