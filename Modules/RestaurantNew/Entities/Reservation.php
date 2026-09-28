<?php
namespace Modules\RestaurantNew\Entities;
class Reservation extends RestnewModel
{
    protected $table='restnew_reservations';
    protected $casts=['reserved_at'=>'datetime','confirmed_at'=>'datetime','seated_at'=>'datetime','cancelled_at'=>'datetime','metadata'=>'array'];
    public function table(){return $this->belongsTo(DiningTable::class,'table_id');}
    public function seatedOrder(){return $this->belongsTo(Order::class,'seated_order_id');}
}
