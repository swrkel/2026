<?php
namespace Modules\RiceMill\Models;

class PackagingMaterialMapping extends BaseRiceMillModel
{
    protected $table='rcm_packaging_material_mappings';
    protected $casts=['active'=>'boolean','bag_size_kg'=>'float','usage_per_bag'=>'float'];

    public function material(){ return $this->belongsTo(PackagingMaterial::class,'material_id'); }
    public function product(){ return $this->belongsTo(RiceProduct::class,'product_id'); }
}
