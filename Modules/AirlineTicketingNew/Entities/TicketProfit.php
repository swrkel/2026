<?php

namespace Modules\AirlineTicketingNew\Entities;

use Modules\AirlineTicketingNew\Entities\Concerns\HasAirlineTicketingScope;

class TicketProfit extends BaseAirlineTicketingModel
{
    use HasAirlineTicketingScope;

    protected $table = 'atn_ticket_profits';
    protected $fillable = ['business_id','business_location_id','store_id','ticket_id','invoice_id','sale_amount','supplier_cost','tax_cost','agent_commission','staff_incentive','other_cost','gross_profit','net_profit','calculated_at','created_by','updated_by'];
    protected $casts = ['sale_amount'=>'decimal:4','supplier_cost'=>'decimal:4','tax_cost'=>'decimal:4','agent_commission'=>'decimal:4','staff_incentive'=>'decimal:4','other_cost'=>'decimal:4','gross_profit'=>'decimal:4','net_profit'=>'decimal:4','calculated_at'=>'datetime'];
}
