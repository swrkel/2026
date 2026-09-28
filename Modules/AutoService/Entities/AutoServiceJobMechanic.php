<?php
namespace Modules\AutoService\Entities;
use Illuminate\Database\Eloquent\Model;
class AutoServiceJobMechanic extends Model { protected $table='auto_service_job_mechanics'; protected $guarded=[]; public function mechanic(){return $this->belongsTo(AutoServiceMechanic::class,'mechanic_id');} public function job(){return $this->belongsTo(AutoServiceJob::class,'job_id');} }
