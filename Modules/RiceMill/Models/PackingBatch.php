<?php
namespace Modules\RiceMill\Models;

class PackingBatch extends BaseRiceMillModel
{
    protected $table='rcm_packing_batches';
    protected $casts=['packed_at'=>'datetime'];

    public function lines(){ return $this->hasMany(PackingLine::class,'packing_batch_id'); }
    public function materialMovements(){ return $this->hasMany(PackagingMaterialMovement::class,'packing_batch_id'); }
}
