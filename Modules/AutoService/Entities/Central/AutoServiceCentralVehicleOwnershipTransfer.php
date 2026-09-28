<?php

namespace Modules\AutoService\Entities\Central;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AutoServiceCentralVehicleOwnershipTransfer extends Model
{
    use SoftDeletes;

    protected $table = 'auto_service_central_vehicle_ownership_transfers';
    protected $guarded = [];

    public function getConnectionName()
    {
        return config('autoservice.central_connection', config('database.default'));
    }

    public function vehicle()
    {
        return $this->belongsTo(AutoServiceCentralVehicle::class, 'central_vehicle_id');
    }
}
