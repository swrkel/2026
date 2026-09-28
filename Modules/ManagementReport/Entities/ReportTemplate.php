<?php
namespace Modules\ManagementReport\Entities;
class ReportTemplate extends TenantModel
{
    protected $table = 'mgmt_report_templates';
    protected $guarded = [];
    protected $casts = ['section_keys' => 'array', 'filter_defaults' => 'array', 'is_default' => 'boolean', 'is_active' => 'boolean'];
}
