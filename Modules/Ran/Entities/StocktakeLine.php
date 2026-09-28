<?php

namespace Modules\Ran\Entities;

class StocktakeLine extends RanModel
{
    protected $table = 'ran_stocktake_lines';
    protected $casts = ['system_quantity'=>'decimal:4','counted_quantity'=>'decimal:4','quantity_variance'=>'decimal:4','system_weight'=>'decimal:3','counted_weight'=>'decimal:3','weight_variance'=>'decimal:3'];
    public function stocktake(){return $this->belongsTo(Stocktake::class);}
    public function stockLot(){return $this->belongsTo(StockLot::class);}
    public function item(){return $this->belongsTo(Item::class);}
}
