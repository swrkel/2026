<?php
namespace Modules\RestaurantNew\Entities;
class Stocktake extends RestnewModel
{
    protected $table='restnew_stocktakes'; protected $casts=['stocktake_date'=>'date','posted_at'=>'datetime']; public function lines(){return $this->hasMany(StocktakeLine::class,'stocktake_id');}
}
