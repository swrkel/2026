<?php

namespace Modules\AutoService\Entities\Central;

use Illuminate\Database\Eloquent\Model;

class AutoServiceCentralVehicleServiceRecord extends Model
{
    protected $table = 'auto_service_central_vehicle_service_records';
    protected $guarded = [];
    protected $casts = [
        'parts_used' => 'array',
        'oils_used' => 'array',
        'documents' => 'array',
        'photos' => 'array',
    ];

    public function getConnectionName()
    {
        return config('autoservice.central_connection', config('database.default'));
    }
}
