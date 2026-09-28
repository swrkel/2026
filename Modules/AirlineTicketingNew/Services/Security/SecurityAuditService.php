<?php
namespace Modules\AirlineTicketingNew\Services\Security;

use Modules\AirlineTicketingNew\Entities\SecurityAuditLog;

class SecurityAuditService
{
    public function record(string $eventCode,array $context=[]): void
    {
        SecurityAuditLog::query()->create([
            'business_id'=>(int)session('business.id'),
            'user_id'=>auth()->id(),
            'event_code'=>$eventCode,
            'ip_address'=>request()->ip(),
            'user_agent'=>request()->userAgent(),
            'route_name'=>optional(request()->route())->getName(),
            'context_json'=>$context,
            'recorded_at'=>now(),
        ]);
    }
}
