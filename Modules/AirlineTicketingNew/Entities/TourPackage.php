<?php

namespace Modules\AirlineTicketingNew\Entities;

use Modules\AirlineTicketingNew\Entities\Concerns\HasAirlineTicketingScope;

class TourPackage extends BaseAirlineTicketingModel
{
    use HasAirlineTicketingScope;

    protected $table = 'atn_tour_packages';

    protected $fillable = [
        'business_id','business_location_id','store_id','package_code','name','package_type',
        'destination_country_code','destination_city','duration_days','duration_nights',
        'currency_code','cost_amount','sale_amount','max_passengers','status','description',
        'created_by','updated_by'
    ];

    protected $casts = [
        'cost_amount' => 'decimal:4','sale_amount' => 'decimal:4',
    ];
}
