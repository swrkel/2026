<?php
namespace Modules\RestaurantNew\Entities;
class GoodsReceipt extends RestnewModel
{
    protected $table='restnew_goods_receipts'; protected $casts=['received_date'=>'date','posted_at'=>'datetime']; public function lines(){return $this->hasMany(GoodsReceiptLine::class,'goods_receipt_id');} public function supplier(){return $this->belongsTo(Supplier::class,'supplier_id');}
}
