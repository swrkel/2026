<?php
namespace Modules\SimpleAudit\Models;
class ActivityLog extends SimpleAuditModel
{
    protected $table = 'sau_activity_logs';
    protected $casts = ['meta_json' => 'array', 'created_at' => 'datetime'];
}
