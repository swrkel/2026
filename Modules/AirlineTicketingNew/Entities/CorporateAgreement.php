<?php

namespace Modules\AirlineTicketingNew\Entities;

use Modules\AirlineTicketingNew\Entities\Concerns\HasAirlineTicketingScope;

class CorporateAgreement extends BaseAirlineTicketingModel
{
    use HasAirlineTicketingScope;

    protected $table = 'atn_corporate_agreements';

    protected $fillable = [
        'business_id','business_location_id','store_id','agreement_no','corporate_customer_id',
        'effective_from','effective_to','credit_limit','credit_days','currency_code',
        'discount_type','discount_value','travel_policy_json','status','remarks',
        'created_by','updated_by'
    ];

    protected $casts = [
        'effective_from' => 'date','effective_to' => 'date','credit_limit' => 'decimal:4',
        'discount_value' => 'decimal:4','travel_policy_json' => 'array',
    ];
}
