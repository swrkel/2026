<?php
namespace Modules\RiceMill\Models;

class PackagingMaterial extends BaseRiceMillModel
{
    protected $table='rcm_packaging_materials';
    protected $casts=['active'=>'boolean','current_qty'=>'float'];

    public function movements(){ return $this->hasMany(PackagingMaterialMovement::class,'material_id'); }
    public function mappings(){ return $this->hasMany(PackagingMaterialMapping::class,'material_id'); }
}
