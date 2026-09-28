<?php
namespace Modules\RiceMill\Models;
class Dispatch extends BaseRiceMillModel { protected $table='rcm_dispatches'; protected $casts=['dispatch_date'=>'date']; public function lines(){return $this->hasMany(DispatchLine::class,'dispatch_id');} }
