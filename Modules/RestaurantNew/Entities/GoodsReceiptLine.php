<?php
namespace Modules\RestaurantNew\Entities;
class GoodsReceiptLine extends RestnewModel
{
    protected $table='restnew_goods_receipt_lines'; protected $casts=['expiry_date'=>'date']; public function ingredient(){return $this->belongsTo(Ingredient::class,'ingredient_id');}
}
