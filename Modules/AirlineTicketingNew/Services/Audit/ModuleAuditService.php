<?php

namespace Modules\AirlineTicketingNew\Services\Audit;

use Modules\AirlineTicketingNew\Entities\AirlineTicketingAuditLog;

class ModuleAuditService
{
    public function record(string $event, $model, array $oldValues = [], array $newValues = []): void
    {
        AirlineTicketingAuditLog::query()->create([
            'business_id' => (int) session('business.id'),
            'business_location_id' => request()->integer('business_location_id') ?: null,
            'store_id' => request()->integer('store_id') ?: null,
            'user_id' => auth()->id(),
            'event' => $event,
            'auditable_type' => is_object($model) ? get_class($model) : null,
            'auditable_id' => is_object($model) ? $model->getKey() : null,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'created_at' => now(),
        ]);
    }
}
