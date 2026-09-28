<?php
namespace Modules\RestaurantNew\Entities;
class Ingredient extends RestnewModel
{
    protected $table='restnew_ingredients';
    protected $casts=['is_active'=>'boolean','track_expiry'=>'boolean'];
    public function supplier(){return $this->belongsTo(Supplier::class,'supplier_id');}
}
