<?php
namespace Modules\AutoService\Entities;
use Illuminate\Database\Eloquent\Model;
class AutoServicePartMovement extends Model { protected $table='auto_service_part_movements'; protected $guarded=[]; public function job(){return $this->belongsTo(AutoServiceJob::class,'job_id');} }
