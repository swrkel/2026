<?php

namespace Modules\Customers\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CustomerActivityService
{
    public function log($businessId, $customerId, $action, $description = null, $userId = null, $locationId = null, array $oldValues = [], array $newValues = [])
    {
        if (!Schema::hasTable('customer_activity_logs')) {
            return;
        }

        $payload = [
            'business_id' => $businessId,
            'customer_id' => $customerId,
            'action' => $action,
            'description' => $description,
            'created_by' => $userId,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        if (Schema::hasColumn('customer_activity_logs', 'business_location_id')) {
            $payload['business_location_id'] = $locationId;
        }

        if (Schema::hasColumn('customer_activity_logs', 'old_values')) {
            $payload['old_values'] = !empty($oldValues) ? json_encode($oldValues) : null;
        }

        if (Schema::hasColumn('customer_activity_logs', 'new_values')) {
            $payload['new_values'] = !empty($newValues) ? json_encode($newValues) : null;
        }

        DB::table('customer_activity_logs')->insert($payload);

        if (Schema::hasTable('customer_audit_trails')) {
            $auditPayload = [
                'business_id' => $businessId,
                'customer_id' => $customerId,
                'business_location_id' => $locationId,
                'action' => $action,
                'description' => $description,
                'old_values' => !empty($oldValues) ? json_encode($oldValues) : null,
                'new_values' => !empty($newValues) ? json_encode($newValues) : null,
                'ip_address' => request()->ip(),
                'user_agent' => substr((string) request()->userAgent(), 0, 255),
                'created_by' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            DB::table('customer_audit_trails')->insert($auditPayload);
        }
    }
}
