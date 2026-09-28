<?php
namespace Modules\AirlineTicketingNew\Entities;

class B2bAgent extends BaseAirlineTicketingModel
{
    protected $table = 'atn_b2b_agents';
    protected $guarded = ['id'];
    protected $casts = [
        'credit_limit' => 'decimal:4',
        'available_credit' => 'decimal:4',
        'is_active' => 'boolean',
    ];
}
