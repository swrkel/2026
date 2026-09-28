<?php
namespace Modules\SimpleAudit\Models;
class ChangeEvent extends SimpleAuditModel
{
    protected $table = 'sau_change_events';
    protected $casts = ['old_data' => 'array', 'new_data' => 'array', 'occurred_at' => 'datetime'];
}
