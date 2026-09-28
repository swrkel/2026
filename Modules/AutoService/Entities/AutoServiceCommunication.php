<?php
namespace Modules\AutoService\Entities;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class AutoServiceCommunication extends Model { use SoftDeletes; protected $table='auto_service_communications'; protected $guarded=[]; }
