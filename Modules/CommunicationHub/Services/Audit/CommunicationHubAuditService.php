<?php

namespace Modules\CommunicationHub\Services\Audit;

use Illuminate\Support\Facades\Auth;
use Modules\CommunicationHub\Entities\CommunicationHubAuditLog;

class CommunicationHubAuditService
{
    public function record(string $action, array $context = []): void
    {
        try {
            CommunicationHubAuditLog::create([
                'action' => $action,
                'user_id' => Auth::id(),
                'ip_address' => request()?->ip(),
                'user_agent' => request()?->userAgent(),
                'meta' => $context,
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
