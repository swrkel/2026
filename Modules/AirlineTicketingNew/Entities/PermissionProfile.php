<?php
namespace Modules\AirlineTicketingNew\Entities;

class PermissionProfile extends BaseAirlineTicketingModel
{
    protected $table='atn_permission_profiles';
    protected $guarded=['id'];
    protected $casts=['permissions_json'=>'array','is_active'=>'boolean'];
}
