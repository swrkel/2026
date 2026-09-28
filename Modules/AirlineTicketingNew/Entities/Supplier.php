<?php

namespace Modules\AirlineTicketingNew\Entities;

use Modules\AirlineTicketingNew\Entities\Concerns\HasAirlineTicketingScope;

class Supplier extends BaseAirlineTicketingModel
{
    use HasAirlineTicketingScope;

    protected $table = 'atn_suppliers';
    protected $fillable = ['business_id','business_location_id','store_id','code','name','supplier_type','contact_person','phone','email','address','currency_code','credit_limit','payment_terms_days','is_active','created_by','updated_by'];
    protected $casts = ['is_active' => 'boolean', 'credit_limit' => 'decimal:4'];
}
