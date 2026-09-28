<?php

namespace Modules\Ran\Entities;

class TransferLine extends RanModel
{
    protected $table = 'ran_transfer_lines';
    protected $casts = ['quantity'=>'decimal:4','weight'=>'decimal:3','received_quantity'=>'decimal:4','received_weight'=>'decimal:3'];
    public function transfer(){return $this->belongsTo(Transfer::class);}
    public function stockLot(){return $this->belongsTo(StockLot::class);}
    public function item(){return $this->belongsTo(Item::class);}
}
