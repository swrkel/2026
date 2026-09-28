<?php

namespace Modules\AirlineTicketingNew\Entities;

use Modules\AirlineTicketingNew\Entities\Concerns\HasAirlineTicketingScope;

class SupplierSettlementLine extends BaseAirlineTicketingModel
{
    use HasAirlineTicketingScope;

    protected $table = 'atn_supplier_settlement_lines';
    protected $fillable = ['business_id','business_location_id','store_id','settlement_id','ticket_id','ticket_no','travel_date','sale_amount','supplier_cost','commission_amount','tax_amount','net_amount','status','created_by','updated_by'];
    protected $casts = ['travel_date'=>'date','sale_amount'=>'decimal:4','supplier_cost'=>'decimal:4','commission_amount'=>'decimal:4','tax_amount'=>'decimal:4','net_amount'=>'decimal:4'];
}
