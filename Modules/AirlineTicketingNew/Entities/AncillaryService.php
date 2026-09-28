<?php
namespace Modules\AirlineTicketingNew\Entities;

class AncillaryService extends BaseAirlineTicketingModel
{
    protected $table = 'atn_ancillary_services';
    protected $guarded = ['id'];
    protected $casts = [
        'cost_amount' => 'decimal:4',
        'sale_amount' => 'decimal:4',
        'is_active' => 'boolean',
    ];
}
