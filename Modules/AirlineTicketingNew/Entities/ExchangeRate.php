<?php
namespace Modules\AirlineTicketingNew\Entities;
class ExchangeRate extends BaseAirlineTicketingModel {
    protected $table='atn_exchange_rates';
    protected $guarded=['id'];
    protected $casts=['rate_date'=>'date','rate'=>'decimal:8','is_locked'=>'boolean'];
}
