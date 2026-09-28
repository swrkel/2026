<?php
namespace Modules\ManagementReport\Entities;
class ReportSetting extends TenantModel
{
    protected $table = 'mgmt_report_settings';
    protected $guarded = [];
    protected $casts = ['setting_value' => 'array', 'is_encrypted' => 'boolean'];
}
