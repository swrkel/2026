<?php

namespace Modules\DistributionNew\Models;

use Illuminate\Database\Eloquent\Model;

class DisnewVehicleFuelEntry extends Model
{
    protected $table = 'disnew_vehicle_fuel_entries';
    protected $fillable = ['business_id','business_location_id','vehicle_id','driver_id','trip_id','fuel_date','fuel_time','fuel_type','litres','unit_price','total_amount','odometer_reading','supplier_name','receipt_no','payment_method','note','created_by','updated_by'];

    protected $casts = [
        'commission_value' => 'decimal:4', 'opening_odometer' => 'decimal:3', 'closing_odometer' => 'decimal:3',
        'distance' => 'decimal:3', 'litres' => 'decimal:3', 'unit_price' => 'decimal:4', 'total_amount' => 'decimal:4',
        'odometer_reading' => 'decimal:3', 'cost_amount' => 'decimal:4', 'amount' => 'decimal:4',
        'base_amount' => 'decimal:4', 'commission_amount' => 'decimal:4', 'is_reimbursable' => 'boolean',
        'approved_at' => 'datetime',
    ];

    public function scopeForBusiness($query, $businessId)
    {
        return $query->where('business_id', $businessId);
    }

    public function scopeForLocation($query, $locationId)
    {
        return $locationId ? $query->where('business_location_id', $locationId) : $query;
    }
}
