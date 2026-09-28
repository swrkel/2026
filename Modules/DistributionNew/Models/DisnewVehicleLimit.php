<?php

namespace Modules\DistributionNew\Models;

use Illuminate\Database\Eloquent\Model;

class DisnewVehicleLimit extends Model
{
    protected $table = 'disnew_vehicle_limits';

    protected $fillable = [
        'business_id', 'vehicle_limit', 'allow_unlimited', 'note', 'created_by', 'updated_by'
    ];

    protected $casts = [
        'vehicle_limit' => 'integer',
        'allow_unlimited' => 'boolean',
    ];
}
