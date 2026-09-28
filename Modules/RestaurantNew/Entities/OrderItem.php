<?php
namespace Modules\RestaurantNew\Entities;

class OrderItem extends RestnewModel
{
    protected $table = 'restnew_order_items';
    protected $casts = [
        'sent_at' => 'datetime',
        'started_at' => 'datetime',
        'ready_at' => 'datetime',
        'stock_posted_at' => 'datetime',
    ];

public function order() { return $this->belongsTo(Order::class, 'order_id'); }
public function menuItem() { return $this->belongsTo(MenuItem::class, 'menu_item_id'); }
public function modifiers() { return $this->hasMany(OrderItemModifier::class, 'order_item_id'); }
}
