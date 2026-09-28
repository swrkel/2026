<?php
namespace Modules\Ran\Entities;
class SaleReturnLine extends RanModel {
    protected $table = 'ran_sale_return_lines';
    protected $casts = ['quantity'=>'decimal:4','weight'=>'decimal:3','amount'=>'decimal:4'];
    public function saleReturn(){return $this->belongsTo(SaleReturn::class);}
    public function saleLine(){return $this->belongsTo(SaleLine::class);}
    public function item(){return $this->belongsTo(Item::class);}
    public function stockLot(){return $this->belongsTo(StockLot::class);}
}
