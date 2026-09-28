<?php

namespace Modules\Ran\Entities;

class RateCard extends RanModel
{
    protected $table = 'ran_rate_cards';
    protected $casts = ['effective_date'=>'date','purchase_rate'=>'decimal:4','sale_rate'=>'decimal:4','is_active'=>'boolean'];
    public function metal(){return $this->belongsTo(Metal::class);}
    public function purity(){return $this->belongsTo(Purity::class);}
}
