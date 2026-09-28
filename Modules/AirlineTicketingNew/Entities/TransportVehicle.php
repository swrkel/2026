<?php

namespace Modules\AirlineTicketingNew\Entities;

use Modules\AirlineTicketingNew\Entities\Concerns\HasAirlineTicketingScope;

class TransportVehicle extends BaseAirlineTicketingModel
{
    use HasAirlineTicketingScope;

    protected $table = 'atn_transport_vehicles';

    protected $fillable = [
        'business_id','business_location_id','store_id','vehicle_no','vehicle_type','make',
        'model','seat_capacity','supplier_id','driver_name','driver_phone','is_active',
        'created_by','updated_by'
    ];

    protected $casts = ['is_active' => 'boolean'];
}
