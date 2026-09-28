<?php
namespace Modules\AirlineTicketingNew\Entities;

class ForecastModel extends BaseAirlineTicketingModel
{
    protected $table = 'atn_forecast_models';
    protected $guarded = ['id'];
    protected $casts = [
        'parameters_json' => 'array',
        'is_active' => 'boolean',
    ];
}
