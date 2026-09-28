<?php

namespace Modules\AirlineTicketingNew\Entities;

use Modules\AirlineTicketingNew\Entities\Concerns\HasAirlineTicketingScope;

class SupplierSettlement extends BaseAirlineTicketingModel
{
    use HasAirlineTicketingScope;

    protected $table = 'atn_supplier_settlements';
    protected $fillable = ['business_id','business_location_id','store_id','settlement_no','supplier_id','settlement_date','period_from','period_to','currency_code','gross_payable','commission_deduction','tax_deduction','other_deduction','net_payable','paid_amount','due_amount','status','remarks','created_by','updated_by'];
    protected $casts = ['settlement_date'=>'date','period_from'=>'date','period_to'=>'date','gross_payable'=>'decimal:4','commission_deduction'=>'decimal:4','tax_deduction'=>'decimal:4','other_deduction'=>'decimal:4','net_payable'=>'decimal:4','paid_amount'=>'decimal:4','due_amount'=>'decimal:4'];
}
