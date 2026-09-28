<?php

namespace Modules\AutoService\Entities\Central;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AutoServiceCentralVehicle extends Model
{
    use SoftDeletes;

    protected $table = 'auto_service_central_vehicles';
    protected $guarded = [];

    public function getConnectionName()
    {
        return config('autoservice.central_connection', config('database.default'));
    }

    public function owners()
    {
        return $this->hasMany(AutoServiceCentralVehicleOwner::class, 'central_vehicle_id');
    }

    public function currentOwner()
    {
        return $this->hasOne(AutoServiceCentralVehicleOwner::class, 'central_vehicle_id')->where('is_current_owner', 1);
    }

    public function serviceRecords()
    {
        return $this->hasMany(AutoServiceCentralVehicleServiceRecord::class, 'central_vehicle_id');
    }
}
