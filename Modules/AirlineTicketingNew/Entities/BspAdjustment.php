<?php
namespace Modules\AirlineTicketingNew\Entities;
class BspAdjustment extends BaseAirlineTicketingModel {
    protected $table='atn_bsp_adjustments';
    protected $guarded=['id'];
    protected $casts=['adjustment_date'=>'date','amount'=>'decimal:4'];
}
