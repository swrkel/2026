<?php
namespace Modules\AirlineTicketingNew\Entities;

class SecurityAuditLog extends BaseAirlineTicketingModel
{
    protected $table='atn_security_audit_logs';
    public $timestamps=false;
    protected $guarded=['id'];
    protected $casts=['context_json'=>'array','recorded_at'=>'datetime'];
}
