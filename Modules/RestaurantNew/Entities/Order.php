<?php
namespace Modules\RestaurantNew\Entities;
class Order extends RestnewModel
{
    use \Illuminate\Database\Eloquent\SoftDeletes;
    protected $table='restnew_orders';
    protected $casts=['opened_at'=>'datetime','sent_at'=>'datetime','ready_at'=>'datetime','paid_at'=>'datetime','cancelled_at'=>'datetime','completed_at'=>'datetime','discount_authorized_at'=>'datetime'];
    public function items(){return $this->hasMany(OrderItem::class,'order_id');}
    public function payments(){return $this->hasMany(Payment::class,'order_id');}
    public function tickets(){return $this->hasMany(KitchenTicket::class,'order_id');}
    public function table(){return $this->belongsTo(DiningTable::class,'table_id');}
    public function shift(){return $this->belongsTo(Shift::class,'shift_id');}
    public function collectionToken(){return $this->hasOne(CollectionToken::class,'order_id');}
    public function reservation(){return $this->belongsTo(Reservation::class,'reservation_id');}
    public function deliveryZone(){return $this->belongsTo(DeliveryZone::class,'delivery_zone_id');}
    public function deliveryDispatch(){return $this->hasOne(DeliveryDispatch::class,'order_id');}
    public function discountRule(){return $this->belongsTo(DiscountRule::class,'discount_rule_id');}
    public function discountUsages(){return $this->hasMany(DiscountUsage::class,'order_id');}
    public function adjustments(){return $this->hasMany(OrderAdjustment::class,'order_id');}
}
