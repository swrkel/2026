<?php
namespace Modules\RiceMill\Models;

class RiceProduct extends BaseRiceMillModel
{
    protected $table='rcm_products';
    protected $casts=['products_new_product_id'=>'integer','paddy_variety_id'=>'integer'];
    public function paddyVariety(){ return $this->belongsTo(PaddyVariety::class,'paddy_variety_id'); }
}
