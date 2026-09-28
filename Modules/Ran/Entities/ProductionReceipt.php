<?php

namespace Modules\Ran\Entities;

class ProductionReceipt extends RanModel
{
    protected $table = 'ran_production_receipts';
    protected $casts = ['receipt_date'=>'date','labour_cost'=>'decimal:4','other_cost'=>'decimal:4'];
    public function lines(){return $this->hasMany(ProductionReceiptLine::class);}
    public function productionOrder(){return $this->belongsTo(ProductionOrder::class);}
}
