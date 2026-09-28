<?php
namespace Modules\DealerManagement\Services;

use Illuminate\Support\Facades\DB;

class AuditService
{
    public function log(string $event, ?string $entityType = null, ?int $entityId = null, $old = null, $new = null, ?object $user = null): void
    {
        $user ??= app(DealerContext::class)->user();
        DB::table('dlr_audit_logs')->insert([
            'business_id' => $user?->business_id ?? (session('business.id') ?? session('business_id') ?? 0),
            'dealer_id' => $user?->dealer_id,
            'dealer_user_id' => $user?->id,
            'event' => $event,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'ip_address' => request()->ip(),
            'user_agent' => substr((string)request()->userAgent(), 0, 500),
            'old_values' => $old === null ? null : json_encode($old),
            'new_values' => $new === null ? null : json_encode($new),
            'created_at' => now(),
        ]);
    }
}
