<?php
namespace Modules\RestaurantNew\Entities;
class Wastage extends RestnewModel
{
    protected $table='restnew_wastages'; protected $casts=['wastage_date'=>'date','approved_at'=>'datetime']; public function lines(){return $this->hasMany(WastageLine::class,'wastage_id');}
}
