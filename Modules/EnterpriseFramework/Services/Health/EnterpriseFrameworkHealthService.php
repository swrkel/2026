<?php

namespace Modules\EnterpriseFramework\Services\Health;

class EnterpriseFrameworkHealthService
{
    public function status(): array
    {
        return [
            'framework' => 'Enterprise Framework',
            'release' => 'EFW005 Security Audit Readiness',
            'report_engine' => class_exists(\Modules\EnterpriseFramework\Services\Report\EnterpriseReportEngine::class),
            'export_engine' => class_exists(\Modules\EnterpriseFramework\Services\Export\ExportEngine::class),
            'print_engine' => class_exists(\Modules\EnterpriseFramework\Services\Print\PrintEngine::class),
            'permission_service' => class_exists(\Modules\EnterpriseFramework\Services\Security\EnterprisePermissionService::class),
            'audit_logger' => class_exists(\Modules\EnterpriseFramework\Services\Audit\EnterpriseAuditLogger::class),
        ];
    }
}
