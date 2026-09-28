<?php
namespace Modules\Audit\Rules\System;

use Modules\Audit\Services\AuditContext;
use Modules\Audit\Support\BaseAuditRule;

class AuditSchemaRule extends BaseAuditRule
{
    protected $module = 'System'; protected $severity = 'critical';
    protected $description = 'Checks that the Audit module schema is complete.';
    public function code(): string { return 'SYS-SCHEMA-001'; }
    public function title(): string { return 'Audit schema integrity'; }
    public function run(AuditContext $context): array
    {
        $required = ['audit_runs','audit_findings','audit_finding_history','audit_resolutions','audit_rule_settings','audit_schedules','audit_exclusions'];
        $out=[];
        foreach ($required as $table) if (!$this->tableExists($table)) $out[]=$this->finding('schema',$table,'Missing Audit table','Required Audit table '.$table.' does not exist.','Table exists','Missing');
        return $out;
    }
}
