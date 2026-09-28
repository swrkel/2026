<?php

namespace Modules\Ran\Entities;

class ProductionOrderLine extends RanModel
{
    protected $table = 'ran_production_order_lines';
    protected $casts = ['planned_quantity'=>'decimal:4','planned_gross_weight'=>'decimal:3','planned_net_weight'=>'decimal:3','planned_stone_weight'=>'decimal:3','wastage_percent'=>'decimal:4','making_charge'=>'decimal:4','specifications'=>'array'];
    public function productionOrder(){return $this->belongsTo(ProductionOrder::class);}
    public function item(){return $this->belongsTo(Item::class);}
    public function design(){return $this->belongsTo(Design::class);}
}
