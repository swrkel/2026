<?php
namespace Modules\Distribution\Entities;

use Illuminate\Database\Eloquent\Model;

class DistributionVehicles extends Model
{
    protected $table = 'distribution_vehicles';

    protected $fillable = [
        'business_id',
        'vehicle_no',
        'vehicle_type',
        'vehicle_brand',
        'vehicle_model',
        'revenue_license_renewal_date',
        'starting_meter',
        'added_by',
    ];

    protected $dates = [
        'revenue_license_renewal_date',
    ];

    /**
     * User who added the record
     */
    public function addedBy()
    {
        return $this->belongsTo(\Modules\Distribution\Entities\Core\User::class, 'added_by');
    }

    public function vehicleMeters()
    {
        return $this->hasMany(DistributionVehicleMeters::class, 'vehicle_id');
    }

    public function dailySummaries()
    {
        return $this->hasMany(DistributionDailySummary::class, 'vehicle_id');
    }

}
