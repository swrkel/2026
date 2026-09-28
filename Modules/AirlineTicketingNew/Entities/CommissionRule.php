<?php

namespace Modules\AirlineTicketingNew\Entities;

use Modules\AirlineTicketingNew\Entities\Concerns\HasAirlineTicketingScope;

class CommissionRule extends BaseAirlineTicketingModel
{
    use HasAirlineTicketingScope;

    protected $table = 'atn_commission_rules';
    protected $fillable = ['business_id','business_location_id','store_id','name','code','party_type','calculation_type','value','airline_id','route_id','travel_class_id','effective_from','effective_to','is_active','created_by','updated_by'];
    protected $casts = ['is_active' => 'boolean', 'value' => 'decimal:4', 'effective_from' => 'date', 'effective_to' => 'date'];
}
