<?php

namespace Modules\AirlineTicketingNew\Entities;

use Modules\AirlineTicketingNew\Entities\Concerns\HasAirlineTicketingScope;

class TaxRule extends BaseAirlineTicketingModel
{
    use HasAirlineTicketingScope;

    protected $table = 'atn_tax_rules';
    protected $fillable = ['business_id','business_location_id','store_id','name','code','calculation_type','rate','fixed_amount','applies_to','country_code','is_active','created_by','updated_by'];
    protected $casts = ['is_active' => 'boolean', 'rate' => 'decimal:4', 'fixed_amount' => 'decimal:4'];
}
