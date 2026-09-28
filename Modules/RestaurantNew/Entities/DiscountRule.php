<?php
namespace Modules\RestaurantNew\Entities;
class DiscountRule extends RestnewModel
{
    protected $table='restnew_discount_rules'; protected $casts=['starts_on'=>'date','ends_on'=>'date','days_json'=>'array'];
}
