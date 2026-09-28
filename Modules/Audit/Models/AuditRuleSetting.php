<?php
namespace Modules\Audit\Models;
use Illuminate\Database\Eloquent\Model;
class AuditRuleSetting extends Model
{
    protected $table = 'audit_rule_settings';
    protected $guarded = [];
    protected $casts = ['is_enabled' => 'boolean', 'settings' => 'array'];
}
