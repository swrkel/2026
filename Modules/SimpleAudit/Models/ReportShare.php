<?php
namespace Modules\SimpleAudit\Models;
class ReportShare extends SimpleAuditModel
{
    protected $table = 'sau_report_shares';
    public $timestamps = true;
    protected $casts = ['filters_json' => 'array', 'date_from' => 'date', 'date_to' => 'date', 'expires_at' => 'datetime'];
}
