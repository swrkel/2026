<?php
namespace Modules\RestaurantNew\Entities;
class StockTransferLine extends RestnewModel
{
    protected $table='restnew_stock_transfer_lines'; public function ingredient(){return $this->belongsTo(Ingredient::class,'ingredient_id');}
}
