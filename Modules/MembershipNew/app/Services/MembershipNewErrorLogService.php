<?php

namespace Modules\MembershipNew\app\Services;

use Modules\MembershipNew\app\Models\MembershipNewErrorLog;

class MembershipNewErrorLogService
{
    public function capture(\Throwable $e, array $context = []): MembershipNewErrorLog
    {
        return MembershipNewErrorLog::create([
            'business_id' => $context['business_id'] ?? \Modules\MembershipNew\app\Support\MembershipNewContext::businessIdOrNull(),
            'user_id' => $context['user_id'] ?? auth()->id(),
            'error_class' => get_class($e),
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'context' => $context,
        ]);
    }
}
