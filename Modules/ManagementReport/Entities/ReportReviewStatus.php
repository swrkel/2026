<?php
namespace Modules\ManagementReport\Entities;
class ReportReviewStatus extends TenantModel
{
    protected $table = 'mgmt_report_review_statuses';
    protected $guarded = [];
    protected $casts = ['reviewed_at' => 'datetime'];
    public function run() { return $this->belongsTo(ReportRun::class, 'report_run_id'); }
}
