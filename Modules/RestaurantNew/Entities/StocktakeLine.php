<?php
namespace Modules\RestaurantNew\Entities;
class StocktakeLine extends RestnewModel
{
    protected $table='restnew_stocktake_lines'; public function ingredient(){return $this->belongsTo(Ingredient::class,'ingredient_id');}
}
