<?php

namespace Modules\Distribution\Entities;

use Illuminate\Database\Eloquent\Model;

class DistributionVehicleMeters extends Model
{
    protected $table = 'distribution_vehicle_meters';

    protected $fillable = [
        'business_id',
        'daily_summary_id',
        'vehicle_id',
        'date',
        'daily_summary_sheet_no',
        'starting_meter',
        'closing_meter',
        'sales_rep_id',
        'route_id',
        'added_by',
    ];

    // Relations

    public function vehicle()
    {
        return $this->belongsTo(DistributionVehicles::class, 'vehicle_id');
    }

    public function dailySummary()
    {
        return $this->belongsTo(DistributionDailySummary::class, 'daily_summary_id');
    }

    public function salesRep()
    {
        return $this->belongsTo(\Modules\Distribution\Entities\Core\User::class, 'sales_rep_id');
    }

    public function route()
    {
        return $this->belongsTo(Distribution_routes::class, 'route_id');
    }

    public function addedBy()
    {
        return $this->belongsTo(\Modules\Distribution\Entities\Core\User::class, 'added_by');
    }
}
