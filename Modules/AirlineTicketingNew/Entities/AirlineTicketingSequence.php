<?php

namespace Modules\AirlineTicketingNew\Entities;

class AirlineTicketingSequence extends BaseAirlineTicketingModel
{
    protected $table = 'atn_sequences';

    protected $casts = [
        'next_number' => 'integer',
        'padding' => 'integer',
        'is_active' => 'boolean',
    ];
}
