<?php

namespace Modules\AutoService\Entities\Central;

use Illuminate\Database\Eloquent\Model;

class AutoServiceCentralVehicleVerification extends Model
{
    protected $table = 'auto_service_central_vehicle_verifications';
    protected $guarded = [];

    public function getConnectionName()
    {
        return config('autoservice.central_connection', config('database.default'));
    }
}
