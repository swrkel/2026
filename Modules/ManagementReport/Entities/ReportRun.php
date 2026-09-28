<?php
namespace Modules\ManagementReport\Entities;
use Illuminate\Database\Eloquent\SoftDeletes;
class ReportRun extends TenantModel
{
    use SoftDeletes;
    protected $table = 'mgmt_report_runs';
    protected $guarded = [];
    protected $casts = ['filter_payload' => 'array', 'snapshot_payload' => 'array', 'generated_at' => 'datetime', 'reviewed_at' => 'datetime'];
    public function sections() { return $this->hasMany(ReportRunSection::class, 'report_run_id')->orderBy('sort_order'); }
    public function shares() { return $this->hasMany(ReportShare::class, 'report_run_id'); }
    public function reviews() { return $this->hasMany(ReportReviewStatus::class, 'report_run_id'); }
}
