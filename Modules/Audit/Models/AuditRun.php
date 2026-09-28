<?php
namespace Modules\Audit\Models;

use Illuminate\Database\Eloquent\Model;

class AuditRun extends Model
{
    protected $table = 'audit_runs';
    protected $guarded = [];
    protected $casts = ['context' => 'array', 'summary' => 'array', 'started_at' => 'datetime', 'finished_at' => 'datetime'];

    public function findings() { return $this->hasMany(AuditFinding::class, 'audit_run_id'); }
}
