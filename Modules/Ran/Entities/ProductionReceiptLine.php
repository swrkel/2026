<?php

namespace Modules\Ran\Entities;

class ProductionReceiptLine extends RanModel
{
    protected $table = 'ran_production_receipt_lines';
    protected $casts = ['quantity'=>'decimal:4','gross_weight'=>'decimal:3','net_weight'=>'decimal:3','stone_weight'=>'decimal:3','fine_weight'=>'decimal:3','material_cost'=>'decimal:4','labour_cost'=>'decimal:4','other_cost'=>'decimal:4','total_cost'=>'decimal:4','sale_price'=>'decimal:4'];
    public function receipt(){return $this->belongsTo(ProductionReceipt::class,'production_receipt_id');}
    public function item(){return $this->belongsTo(Item::class);}
}
