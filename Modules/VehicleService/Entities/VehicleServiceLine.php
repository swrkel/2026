<?php

namespace Modules\VehicleService\Entities;

use Illuminate\Database\Eloquent\Model;

class VehicleServiceLine extends Model
{
    protected $table = 'vehicle_service_lines';
    protected $guarded = ['id'];

    public function job()
    {
        return $this->belongsTo(VehicleServiceJob::class, 'vehicle_service_job_id');
    }
}
