<?php

namespace Modules\AirlineTicketingNew\Entities;

use Modules\AirlineTicketingNew\Entities\Concerns\HasAirlineTicketingScope;

class VisaApplication extends BaseAirlineTicketingModel
{
    use HasAirlineTicketingScope;

    protected $table = 'atn_visa_applications';

    protected $fillable = [
        'business_id','business_location_id','store_id','application_no','passenger_id','country_code',
        'visa_type','embassy_name','appointment_at','submission_date','expected_completion_date',
        'visa_fee','service_fee','currency_code','status','remarks','created_by','updated_by'
    ];

    protected $casts = [
        'appointment_at' => 'datetime','submission_date' => 'date',
        'expected_completion_date' => 'date','visa_fee' => 'decimal:4','service_fee' => 'decimal:4',
    ];
}
