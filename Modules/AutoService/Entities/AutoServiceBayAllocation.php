<?php
namespace Modules\AutoService\Entities;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class AutoServiceBayAllocation extends Model { use SoftDeletes; protected $table='auto_service_bay_allocations'; protected $guarded=[]; }
