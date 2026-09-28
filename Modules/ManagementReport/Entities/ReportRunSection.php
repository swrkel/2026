<?php
namespace Modules\ManagementReport\Entities;
class ReportRunSection extends TenantModel
{
    protected $table = 'mgmt_report_run_sections';
    protected $guarded = [];
    protected $casts = ['section_payload' => 'array'];
    public function run() { return $this->belongsTo(ReportRun::class, 'report_run_id'); }
}
