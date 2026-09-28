<?php

namespace Modules\Ran\Entities;

class ItemComponent extends RanModel
{
    protected $table = 'ran_item_components';
    protected $casts = ['quantity'=>'decimal:4','weight'=>'decimal:3','wastage_percent'=>'decimal:4','unit_cost'=>'decimal:4'];
    public function item(){return $this->belongsTo(Item::class);}
    public function metal(){return $this->belongsTo(Metal::class);}
    public function purity(){return $this->belongsTo(Purity::class);}
    public function gemstone(){return $this->belongsTo(Gemstone::class);}
}
