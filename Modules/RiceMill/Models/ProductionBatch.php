<?php
namespace Modules\RiceMill\Models;
class ProductionBatch extends BaseRiceMillModel { protected $table='rcm_production_batches'; protected $casts=['started_at'=>'datetime','completed_at'=>'datetime']; public function inputs(){return $this->hasMany(ProductionInput::class,'production_batch_id');} public function outputs(){return $this->hasMany(ProductionOutput::class,'production_batch_id');} }
