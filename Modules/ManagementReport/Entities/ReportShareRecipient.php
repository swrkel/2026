<?php
namespace Modules\ManagementReport\Entities;
class ReportShareRecipient extends TenantModel
{
    protected $table = 'mgmt_report_share_recipients';
    protected $guarded = [];
    protected $casts = ['delivered_at' => 'datetime', 'failed_at' => 'datetime', 'provider_response' => 'array'];
    public function share() { return $this->belongsTo(ReportShare::class, 'report_share_id'); }
}
