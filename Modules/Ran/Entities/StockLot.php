<?php

namespace Modules\Ran\Entities;

class StockLot extends RanModel
{
    protected $table = 'ran_stock_lots';
    protected $casts = ['received_on'=>'date','quantity'=>'decimal:4','gross_weight'=>'decimal:3','net_weight'=>'decimal:3','stone_weight'=>'decimal:3','fine_weight'=>'decimal:3','unit_cost'=>'decimal:4','total_cost'=>'decimal:4','sale_price'=>'decimal:4'];
    public function item(){return $this->belongsTo(Item::class);}
    public function movements(){return $this->hasMany(StockMovement::class);}
}
