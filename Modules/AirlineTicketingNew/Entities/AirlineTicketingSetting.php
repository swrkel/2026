<?php

namespace Modules\AirlineTicketingNew\Entities;

class AirlineTicketingSetting extends BaseAirlineTicketingModel
{
    protected $table = 'atn_settings';

    protected $casts = [
        'value_json' => 'array',
        'is_active' => 'boolean',
    ];
}
