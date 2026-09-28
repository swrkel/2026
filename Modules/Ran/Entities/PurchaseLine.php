<?php
namespace Modules\Ran\Entities;
class PurchaseLine extends RanModel {
    protected $table = 'ran_purchase_lines';
    protected $casts = ['quantity'=>'decimal:4','gross_weight'=>'decimal:3','net_weight'=>'decimal:3','stone_weight'=>'decimal:3','unit_cost'=>'decimal:4','line_total'=>'decimal:4','sale_price'=>'decimal:4'];
    public function purchase(){return $this->belongsTo(Purchase::class);}
    public function item(){return $this->belongsTo(Item::class);}
}
