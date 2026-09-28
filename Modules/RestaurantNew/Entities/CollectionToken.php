<?php
namespace Modules\RestaurantNew\Entities;

class CollectionToken extends RestnewModel
{
    protected $table = 'restnew_collection_tokens';
    protected $casts = [
        'called_at' => 'datetime',
        'ready_at' => 'datetime',
        'collected_at' => 'datetime',
    ];

public function order() { return $this->belongsTo(Order::class, 'order_id'); }
}
