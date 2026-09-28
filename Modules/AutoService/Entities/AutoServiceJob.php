<?php
namespace Modules\AutoService\Entities;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class AutoServiceJob extends Model { use SoftDeletes; protected $table='auto_service_jobs'; protected $guarded=[]; public function lines(){return $this->hasMany(AutoServiceJobLine::class,'job_id');} public function payments(){return $this->hasMany(AutoServicePayment::class,'job_id');} public function mechanics(){return $this->hasMany(AutoServiceJobMechanic::class,'job_id');} public function partMovements(){return $this->hasMany(AutoServicePartMovement::class,'job_id');} }
