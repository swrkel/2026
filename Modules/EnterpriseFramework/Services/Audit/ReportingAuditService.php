<?php

namespace Modules\EnterpriseFramework\Services\Audit;

class ReportingAuditService
{
    public function log(string $action, array $payload = []): void
    {
        logger()->info('EnterpriseFramework reporting audit', [
            'action' => $action,
            'user_id' => auth()->id(),
            'business_id' => session('business.id'),
            'payload' => $payload,
        ]);
    }
}
