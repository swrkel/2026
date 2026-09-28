<?php
namespace Modules\AirlineTicketingNew\Entities;
class AccountMapping extends BaseAirlineTicketingModel {
    protected $table='atn_account_mappings';
    protected $guarded=['id'];
    protected $casts=['is_active'=>'boolean'];
}
