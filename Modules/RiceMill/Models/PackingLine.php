<?php
namespace Modules\RiceMill\Models;

class PackingLine extends BaseRiceMillModel
{
    protected $table='rcm_packing_lines';
    protected $casts=['bag_size_kg'=>'float','total_qty'=>'float'];

    public function product(){ return $this->belongsTo(RiceProduct::class,'product_id'); }
    public function sources(){ return $this->hasMany(PackingSource::class,'packing_line_id'); }
}
