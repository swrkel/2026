<?php
namespace Modules\AirlineTicketingNew\Entities;

class SupplierServiceAgreement extends BaseAirlineTicketingModel
{
    protected $table = 'atn_supplier_service_agreements';
    protected $guarded = ['id'];
    protected $casts = [
        'effective_from' => 'date',
        'effective_to' => 'date',
        'commission_rate' => 'decimal:4',
        'is_active' => 'boolean',
    ];
}
