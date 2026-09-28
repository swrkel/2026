<?php
namespace Modules\AirlineTicketingNew\Entities;

class TravelInsurancePolicy extends BaseAirlineTicketingModel
{
    protected $table = 'atn_travel_insurance_policies';
    protected $guarded = ['id'];
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'premium_amount' => 'decimal:4',
        'coverage_amount' => 'decimal:4',
    ];
}
