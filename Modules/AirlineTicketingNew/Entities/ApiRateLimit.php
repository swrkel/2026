<?php
namespace Modules\AirlineTicketingNew\Entities;

class ApiRateLimit extends BaseAirlineTicketingModel
{
    protected $table='atn_api_rate_limits';
    protected $guarded=['id'];
    protected $casts=['window_started_at'=>'datetime'];
}
