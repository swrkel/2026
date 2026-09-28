<?php

namespace Modules\MyHealthMembers\Services;

use Modules\MyHealthMembers\Entities\MyHealthAuditLog;

class MyHealthAuditService
{
    public function log(?int $memberId, string $section, string $action, ?string $description = null): void
    {
        MyHealthAuditLog::create([
            'member_id' => $memberId,
            'business_id' => request()->session()->get('user.business_id') ?: request()->session()->get('business.id'),
            'user_id' => auth()->id(),
            'section' => $section,
            'action' => $action,
            'description' => $description,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'logged_at' => now(),
        ]);
    }
}
