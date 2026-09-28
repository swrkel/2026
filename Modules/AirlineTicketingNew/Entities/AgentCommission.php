<?php

namespace Modules\AirlineTicketingNew\Entities;

use Modules\AirlineTicketingNew\Entities\Concerns\HasAirlineTicketingScope;

class AgentCommission extends BaseAirlineTicketingModel
{
    use HasAirlineTicketingScope;

    protected $table = 'atn_agent_commissions';
    protected $fillable = ['business_id','business_location_id','store_id','commission_no','agent_id','ticket_id','invoice_id','commission_date','calculation_type','basis_amount','rate','commission_amount','paid_amount','due_amount','status','remarks','created_by','updated_by'];
    protected $casts = ['commission_date'=>'date','basis_amount'=>'decimal:4','rate'=>'decimal:4','commission_amount'=>'decimal:4','paid_amount'=>'decimal:4','due_amount'=>'decimal:4'];
}
