<?php
namespace Modules\AirlineTicketingNew\Entities;
class LoyaltyTransaction extends BaseAirlineTicketingModel {
    protected $table='atn_loyalty_transactions';
    protected $guarded=['id'];
    protected $casts=['transaction_date'=>'date','points'=>'decimal:2','balance_after'=>'decimal:2'];
}
