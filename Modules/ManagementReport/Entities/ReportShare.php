<?php
namespace Modules\ManagementReport\Entities;
class ReportShare extends TenantModel
{
    protected $table = 'mgmt_report_shares';
    protected $guarded = [];
    protected $casts = ['expires_at' => 'datetime', 'revoked_at' => 'datetime', 'last_viewed_at' => 'datetime', 'delivered_at' => 'datetime', 'failed_at' => 'datetime', 'provider_response' => 'array'];
    public function run() { return $this->belongsTo(ReportRun::class, 'report_run_id'); }
    public function recipients() { return $this->hasMany(ReportShareRecipient::class, 'report_share_id'); }
    public function isAvailable() { return !$this->revoked_at && (!$this->expires_at || $this->expires_at->isFuture()); }
}
