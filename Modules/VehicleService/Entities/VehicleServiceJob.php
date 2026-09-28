<?php

namespace Modules\VehicleService\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class VehicleServiceJob extends Model
{
    use SoftDeletes;

    protected $table = 'vehicle_service_jobs';
    protected $guarded = ['id'];

    public function lines()
    {
        return $this->hasMany(VehicleServiceLine::class, 'vehicle_service_job_id')->orderBy('sort_order')->orderBy('id');
    }
}
