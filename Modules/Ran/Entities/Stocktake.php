<?php

namespace Modules\Ran\Entities;

class Stocktake extends RanModel
{
    protected $table = 'ran_stocktakes';
    protected $casts = ['stocktake_date'=>'date','posted_at'=>'datetime'];
    public function lines(){return $this->hasMany(StocktakeLine::class);}
}
