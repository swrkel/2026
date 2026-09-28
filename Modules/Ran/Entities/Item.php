<?php

namespace Modules\Ran\Entities;

class Item extends RanModel
{
    protected $table = 'ran_items';
    protected $casts = ['standard_gross_weight'=>'decimal:3','standard_net_weight'=>'decimal:3','standard_stone_weight'=>'decimal:3','making_charge'=>'decimal:4','default_sale_price'=>'decimal:4','minimum_sale_price'=>'decimal:4','serialized'=>'boolean','is_active'=>'boolean'];
    public function components(){return $this->hasMany(ItemComponent::class);}
    public function metal(){return $this->belongsTo(Metal::class);}
    public function purity(){return $this->belongsTo(Purity::class);}
    public function design(){return $this->belongsTo(Design::class);}
    public function stockLots(){return $this->hasMany(StockLot::class);}
}
