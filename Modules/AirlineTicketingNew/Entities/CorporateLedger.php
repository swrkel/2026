<?php

namespace Modules\AirlineTicketingNew\Entities;

use Modules\AirlineTicketingNew\Entities\Concerns\HasAirlineTicketingScope;

class CorporateLedger extends BaseAirlineTicketingModel
{
    use HasAirlineTicketingScope;

    protected $table = 'atn_corporate_ledgers';

    protected $fillable = [
        'business_id','business_location_id','store_id','corporate_customer_id','transaction_date',
        'reference_type','reference_id','reference_no','description','debit','credit','balance',
        'currency_code','created_by','updated_by'
    ];

    protected $casts = [
        'transaction_date' => 'date','debit' => 'decimal:4','credit' => 'decimal:4','balance' => 'decimal:4',
    ];
}
