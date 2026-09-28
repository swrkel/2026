<?php
namespace Modules\Audit\Models;
use Illuminate\Database\Eloquent\Model;
class AuditFindingHistory extends Model
{
    protected $table = 'audit_finding_history';
    protected $guarded = [];
    protected $casts = ['meta' => 'array'];
}
