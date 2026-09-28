<?php
namespace Modules\AirlineTicketingNew\Entities;

class TravelPolicy extends BaseAirlineTicketingModel
{
    protected $table = 'atn_travel_policies';
    protected $guarded = ['id'];
    protected $casts = [
        'rules_json' => 'array',
        'effective_from' => 'date',
        'effective_to' => 'date',
        'is_active' => 'boolean',
    ];
}
