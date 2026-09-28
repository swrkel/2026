<?php
namespace Modules\RestaurantNew\Entities;
class WastageLine extends RestnewModel
{
    protected $table='restnew_wastage_lines'; public function ingredient(){return $this->belongsTo(Ingredient::class,'ingredient_id');}
}
