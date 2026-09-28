<?php
namespace Modules\RestaurantNew\Services;
use Modules\RestaurantNew\Entities\AuditLog;
class AuditService
{
    public function record(string $event, string $entityType, ?int $entityId, array $old = [], array $new = [], array $metadata = []): void
    {
        try {
            AuditLog::create([
                'business_id'=>app(TenantScopeService::class)->businessId(),
                'entity_type'=>$entityType,'entity_id'=>$entityId,'event'=>$event,
                'old_values'=>$old ?: null,'new_values'=>$new ?: null,'metadata'=>$metadata ?: null,
                'ip_address'=>request()->ip(),'user_agent'=>substr((string) request()->userAgent(),0,500),
                'created_by'=>auth()->id(),
            ]);
        } catch (\Throwable $e) { report($e); }
    }
}
