<?php

namespace Modules\MembershipNew\app\Services;

use Modules\MembershipNew\app\Models\MembershipNewAuditLog;

class MembershipNewAuditService
{
    public function log(string $action, array $data = []): MembershipNewAuditLog
    {
        return MembershipNewAuditLog::create([
            'business_id' => $data['business_id'] ?? \Modules\MembershipNew\app\Support\MembershipNewContext::businessIdOrNull(),
            'user_id' => $data['user_id'] ?? auth()->id(),
            'action' => $action,
            'entity_type' => $data['entity_type'] ?? null,
            'entity_id' => $data['entity_id'] ?? null,
            'old_values' => $data['old_values'] ?? null,
            'new_values' => $data['new_values'] ?? null,
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
            'meta' => $data['meta'] ?? null,
        ]);
    }
}
