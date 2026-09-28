<?php
namespace Modules\AirlineTicketingNew\Entities;

class ApprovalMatrix extends BaseAirlineTicketingModel
{
    protected $table = 'atn_approval_matrices';
    protected $guarded = ['id'];
    protected $casts = [
        'minimum_amount' => 'decimal:4',
        'maximum_amount' => 'decimal:4',
        'is_active' => 'boolean',
    ];
}
