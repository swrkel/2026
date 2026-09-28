<?php
namespace Modules\AirlineTicketingNew\Entities;

class EnterpriseSetting extends BaseAirlineTicketingModel
{
    protected $table='atn_enterprise_settings';
    protected $guarded=['id'];
    protected $casts=['setting_value_json'=>'array','is_locked'=>'boolean'];
}
