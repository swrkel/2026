<?php

namespace Modules\AirlineTicketingNew\Entities;

use Modules\AirlineTicketingNew\Entities\Concerns\HasAirlineTicketingScope;

class StaffIncentive extends BaseAirlineTicketingModel
{
    use HasAirlineTicketingScope;

    protected $table = 'atn_staff_incentives';

    protected $fillable = [
        'business_id','business_location_id','store_id','incentive_no','user_id','ticket_id','invoice_id',
        'incentive_date','calculation_type','basis_amount','rate','incentive_amount',
        'paid_amount','due_amount','status','remarks','created_by','updated_by'
    ];

    protected $casts = [
        'incentive_date' => 'date','basis_amount' => 'decimal:4','rate' => 'decimal:4',
        'incentive_amount' => 'decimal:4','paid_amount' => 'decimal:4','due_amount' => 'decimal:4',
    ];
}
