<?php
namespace Modules\RestaurantNew\Entities;
class StockTransfer extends RestnewModel
{
    protected $table='restnew_stock_transfers'; protected $casts=['transfer_date'=>'date','dispatched_at'=>'datetime','received_at'=>'datetime']; public function lines(){return $this->hasMany(StockTransferLine::class,'stock_transfer_id');}
}
