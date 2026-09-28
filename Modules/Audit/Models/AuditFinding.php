<?php
namespace Modules\Audit\Models;

use Illuminate\Database\Eloquent\Model;

class AuditFinding extends Model
{
    protected $table = 'audit_findings';
    protected $guarded = [];
    protected $casts = ['payload' => 'array', 'first_seen_at' => 'datetime', 'last_seen_at' => 'datetime', 'resolved_at' => 'datetime'];

    public function run() { return $this->belongsTo(AuditRun::class, 'audit_run_id'); }
    public function history() { return $this->hasMany(AuditFindingHistory::class, 'audit_finding_id')->latest('id'); }
    public function resolutions() { return $this->hasMany(AuditResolution::class, 'audit_finding_id')->latest('id'); }
}
