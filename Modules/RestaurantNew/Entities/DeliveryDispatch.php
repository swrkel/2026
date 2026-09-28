<?php
namespace Modules\RestaurantNew\Entities;
class DeliveryDispatch extends RestnewModel
{
    protected $table='restnew_delivery_dispatches'; protected $casts=['assigned_at'=>'datetime','dispatched_at'=>'datetime','delivered_at'=>'datetime']; public function order(){return $this->belongsTo(Order::class,'order_id');} public function zone(){return $this->belongsTo(DeliveryZone::class,'delivery_zone_id');}
}
