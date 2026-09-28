<?php
namespace Modules\RiceMill\Models;

class PackingSource extends BaseRiceMillModel
{
    protected $table='rcm_packing_sources';
    protected $casts=['quantity'=>'float'];

    public function line(){ return $this->belongsTo(PackingLine::class,'packing_line_id'); }
    public function productionBatch(){ return $this->belongsTo(ProductionBatch::class,'production_batch_id'); }
}
