<?php
namespace Modules\RiceMill\Models;

class PackagingMaterialMovement extends BaseRiceMillModel
{
    protected $table='rcm_packaging_material_movements';
    protected $casts=['movement_date'=>'date','quantity'=>'float','signed_quantity'=>'float'];

    public function material(){ return $this->belongsTo(PackagingMaterial::class,'material_id'); }
    public function packingBatch(){ return $this->belongsTo(PackingBatch::class,'packing_batch_id'); }
}
