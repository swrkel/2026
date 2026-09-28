<?php

namespace Modules\AirlineTicketingNew\Entities;

use Modules\AirlineTicketingNew\Entities\Concerns\HasAirlineTicketingScope;

class Agent extends BaseAirlineTicketingModel
{
    use HasAirlineTicketingScope;

    protected $table = 'atn_agents';
    protected $fillable = ['business_id','business_location_id','store_id','code','name','agent_type','contact_person','phone','email','address','commission_type','commission_value','credit_limit','is_active','created_by','updated_by'];
    protected $casts = ['is_active' => 'boolean', 'credit_limit' => 'decimal:4'];
}
