<?php
namespace Modules\AirlineTicketingNew\Entities;

class ReissueQuote extends BaseAirlineTicketingModel
{
    protected $table = 'atn_reissue_quotes';
    protected $guarded = ['id'];
    protected $casts = [
        'quoted_at' => 'datetime',
        'fare_difference' => 'decimal:4',
        'tax_difference' => 'decimal:4',
        'penalty_amount' => 'decimal:4',
        'service_fee' => 'decimal:4',
        'total_collectable' => 'decimal:4',
        'details_json' => 'array',
    ];
}
