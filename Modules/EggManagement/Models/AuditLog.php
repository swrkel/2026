<?php
namespace Modules\EggManagement\Models;

class AuditLog extends EggModel
{
    protected $table = 'egg_audit_logs';
    protected $casts = ['before_data'=>'array','after_data'=>'array'];
}
