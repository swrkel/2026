<?php
namespace Modules\AirlineTicketingNew\Entities;
class BspPeriod extends BaseAirlineTicketingModel {
    protected $table='atn_bsp_periods';
    protected $guarded=['id'];
    protected $casts=['period_from'=>'date','period_to'=>'date','payment_due_date'=>'date','closed_at'=>'datetime'];
}
