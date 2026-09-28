<?php
namespace Modules\DistributionNew\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DisnewTrip extends Model
{
    use SoftDeletes;
    protected $table = 'disnew_trips';
    protected $guarded = ['id'];
    protected $fillable = [
        'business_id',
    'location_id',
    'trip_no',
    'vehicle_id',
    'driver_id',
    'helper_id',
    'route_id',
    'warehouse_id',
    'trip_date',
    'status',
    'capacity_weight',
    'capacity_volume',
    'loaded_weight',
    'loaded_volume',
    'created_by'
    ];
}
