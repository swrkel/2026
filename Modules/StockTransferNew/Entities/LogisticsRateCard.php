<?php

namespace Modules\StockTransferNew\Entities;

use Illuminate\Database\Eloquent\Model;

class LogisticsRateCard extends Model
{
    protected $table = 'stn_logistics_rate_cards';

    protected $fillable = [
        'business_id', 'location_id', 'store_id', 'transporter_name', 'route_code',
        'route_name', 'vehicle_type', 'rate_basis', 'base_rate', 'rate_per_km',
        'rate_per_kg', 'rate_per_cbm', 'minimum_charge', 'effective_from',
        'effective_to', 'status', 'created_by', 'updated_by'
    ];

    protected $casts = [
        'base_rate' => 'decimal:4',
        'rate_per_km' => 'decimal:4',
        'rate_per_kg' => 'decimal:4',
        'rate_per_cbm' => 'decimal:4',
        'minimum_charge' => 'decimal:4',
        'effective_from' => 'date',
        'effective_to' => 'date',
    ];
}
