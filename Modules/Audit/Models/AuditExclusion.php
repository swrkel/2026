<?php
namespace Modules\Audit\Models;
use Illuminate\Database\Eloquent\Model;
class AuditExclusion extends Model
{
    protected $table = 'audit_exclusions';
    protected $guarded = [];
    protected $casts = ['is_active' => 'boolean', 'expires_at' => 'datetime'];
}
