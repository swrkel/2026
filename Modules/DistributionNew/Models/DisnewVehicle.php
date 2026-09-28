<?php

namespace Modules\DistributionNew\Models;

use Illuminate\Database\Eloquent\Model;

class DisnewVehicle extends Model
{
    protected $table = 'disnew_vehicles';

    protected $fillable = [
        'business_id', 'business_location_id', 'vehicle_no', 'vehicle_name', 'vehicle_type',
        'capacity_qty', 'capacity_volume', 'driver_name', 'driver_mobile', 'helper_name',
        'helper_mobile', 'status', 'note', 'created_by', 'updated_by'
    ];

    protected $casts = [
        'capacity_qty' => 'decimal:4',
        'capacity_volume' => 'decimal:4',
    ];

    public function scopeForBusiness($query, $businessId)
    {
        return $query->where('business_id', $businessId);
    }

    public function scopeForLocation($query, $locationId)
    {
        return $locationId ? $query->where('business_location_id', $locationId) : $query;
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
