<?php
namespace Modules\AirlineTicketingNew\Entities;

class RefundRule extends BaseAirlineTicketingModel
{
    protected $table = 'atn_refund_rules';
    protected $guarded = ['id'];
    protected $casts = [
        'effective_from' => 'date',
        'effective_to' => 'date',
        'penalty_amount' => 'decimal:4',
        'penalty_percent' => 'decimal:4',
        'is_active' => 'boolean',
    ];
}
