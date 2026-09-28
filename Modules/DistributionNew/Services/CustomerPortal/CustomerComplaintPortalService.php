<?php

namespace Modules\DistributionNew\Services\CustomerPortal;

use Illuminate\Support\Facades\DB;

class CustomerComplaintPortalService
{
    public function createComplaint(array $payload, int $businessId, int $customerId): int
    {
        return DB::table('disnew_customer_complaints')->insertGetId([
            'business_id' => $businessId,
            'location_id' => $payload['location_id'] ?? null,
            'customer_id' => $customerId,
            'complaint_no' => $payload['complaint_no'] ?? ('DNCMP-' . now()->format('YmdHis')),
            'category' => $payload['category'] ?? 'other',
            'priority' => $payload['priority'] ?? 'normal',
            'status' => 'open',
            'subject' => $payload['subject'],
            'description' => $payload['description'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
