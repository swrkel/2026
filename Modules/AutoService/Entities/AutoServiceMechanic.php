<?php
namespace Modules\AutoService\Entities;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class AutoServiceMechanic extends Model { use SoftDeletes; protected $table='auto_service_mechanics'; protected $guarded=[]; public function assignments(){return $this->hasMany(AutoServiceJobMechanic::class,'mechanic_id');} }
