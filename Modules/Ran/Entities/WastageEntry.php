<?php

namespace Modules\Ran\Entities;

class WastageEntry extends RanModel
{
    protected $table = 'ran_wastage_entries';
    protected $casts = ['entry_date'=>'date','weight'=>'decimal:3','fine_weight'=>'decimal:3','value'=>'decimal:4','approved_at'=>'datetime'];
    public function productionOrder(){return $this->belongsTo(ProductionOrder::class);}
}
