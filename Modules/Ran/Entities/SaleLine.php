<?php
namespace Modules\Ran\Entities;
class SaleLine extends RanModel {
    protected $table = 'ran_sale_lines';
    protected $casts = ['quantity'=>'decimal:4','gross_weight'=>'decimal:3','net_weight'=>'decimal:3','stone_weight'=>'decimal:3','metal_rate'=>'decimal:4','metal_value'=>'decimal:4','stone_value'=>'decimal:4','making_charge'=>'decimal:4','other_charge'=>'decimal:4','discount_amount'=>'decimal:4','tax_amount'=>'decimal:4','unit_price'=>'decimal:4','line_total'=>'decimal:4','cost_amount'=>'decimal:4'];
    public function sale(){return $this->belongsTo(Sale::class);}
    public function item(){return $this->belongsTo(Item::class);}
    public function stockLot(){return $this->belongsTo(StockLot::class);}
}
