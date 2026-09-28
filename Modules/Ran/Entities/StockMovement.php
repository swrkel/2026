<?php

namespace Modules\Ran\Entities;

class StockMovement extends RanModel
{
    protected $table = 'ran_stock_movements';
    protected $casts = ['movement_at'=>'datetime','quantity_in'=>'decimal:4','quantity_out'=>'decimal:4','weight_in'=>'decimal:3','weight_out'=>'decimal:3','unit_cost'=>'decimal:4'];
    public function item(){return $this->belongsTo(Item::class);}
    public function stockLot(){return $this->belongsTo(StockLot::class);}
}
