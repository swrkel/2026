<?php
namespace Modules\AirlineTicketingNew\Entities;

class TransferRoute extends BaseAirlineTicketingModel
{
    protected $table = 'atn_transfer_routes';
    protected $guarded = ['id'];
    protected $casts = [
        'distance_km' => 'decimal:3',
        'base_cost' => 'decimal:4',
        'base_sale' => 'decimal:4',
        'is_active' => 'boolean',
    ];
}
