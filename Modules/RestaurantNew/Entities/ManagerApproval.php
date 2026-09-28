<?php
namespace Modules\RestaurantNew\Entities;
class ManagerApproval extends RestnewModel
{
    protected $table='restnew_manager_approvals'; protected $casts=['payload_json'=>'array','approved_at'=>'datetime'];
}
