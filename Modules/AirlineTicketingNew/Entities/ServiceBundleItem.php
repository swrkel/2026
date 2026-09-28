<?php
namespace Modules\AirlineTicketingNew\Entities;

class ServiceBundleItem extends BaseAirlineTicketingModel
{
    protected $table = 'atn_service_bundle_items';
    protected $guarded = ['id'];
    protected $casts = [
        'quantity' => 'decimal:3',
        'unit_price' => 'decimal:4',
    ];
}
