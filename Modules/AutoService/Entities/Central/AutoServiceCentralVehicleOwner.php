<?php

namespace Modules\AutoService\Entities\Central;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AutoServiceCentralVehicleOwner extends Model
{
    use SoftDeletes;

    protected $table = 'auto_service_central_vehicle_owners';
    protected $guarded = [];

    public function getConnectionName()
    {
        return config('autoservice.central_connection', config('database.default'));
    }
}
