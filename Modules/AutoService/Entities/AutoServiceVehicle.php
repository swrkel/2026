<?php
namespace Modules\AutoService\Entities;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class AutoServiceVehicle extends Model { use SoftDeletes; protected $table='auto_service_vehicles'; protected $guarded=[]; }
