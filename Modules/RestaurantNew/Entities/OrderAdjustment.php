<?php
namespace Modules\RestaurantNew\Entities;
class OrderAdjustment extends RestnewModel
{
    protected $table='restnew_order_adjustments'; protected $casts=['before_json'=>'array','after_json'=>'array','approved_at'=>'datetime'];
}
