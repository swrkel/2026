<?php
namespace Modules\AirlineTicketingNew\Entities;

class ServiceBundle extends BaseAirlineTicketingModel
{
    protected $table = 'atn_service_bundles';
    protected $guarded = ['id'];
    protected $casts = [
        'bundle_price' => 'decimal:4',
        'is_active' => 'boolean',
    ];
}
