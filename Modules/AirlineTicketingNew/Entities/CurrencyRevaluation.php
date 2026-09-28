<?php
namespace Modules\AirlineTicketingNew\Entities;
class CurrencyRevaluation extends BaseAirlineTicketingModel {
    protected $table='atn_currency_revaluations';
    protected $guarded=['id'];
    protected $casts=['revaluation_date'=>'date','old_rate'=>'decimal:8','new_rate'=>'decimal:8','foreign_balance'=>'decimal:4','old_base_value'=>'decimal:4','new_base_value'=>'decimal:4','gain_loss'=>'decimal:4'];
}
