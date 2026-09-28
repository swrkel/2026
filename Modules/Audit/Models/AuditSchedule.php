<?php
namespace Modules\Audit\Models;
use Illuminate\Database\Eloquent\Model;
class AuditSchedule extends Model
{
    protected $table = 'audit_schedules';
    protected $guarded = [];
    protected $casts = ['is_enabled' => 'boolean', 'modules' => 'array', 'last_run_at' => 'datetime', 'next_run_at' => 'datetime'];
}
