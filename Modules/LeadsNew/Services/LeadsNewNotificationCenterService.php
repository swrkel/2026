<?php

namespace Modules\LeadsNew\Services;

use Illuminate\Support\Facades\DB;

class LeadsNewNotificationCenterService
{
    public function create(int $businessId, int $userId, string $title, string $message, array $payload = []): int
    {
        return DB::table('leads_new_notifications')->insertGetId([
            'business_id' => $businessId,
            'user_id' => $userId,
            'title' => $title,
            'message' => $message,
            'payload' => json_encode($payload),
            'is_read' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function unread(int $businessId, int $userId)
    {
        return DB::table('leads_new_notifications')
            ->where('business_id', $businessId)
            ->where('user_id', $userId)
            ->where('is_read', 0)
            ->latest('id')
            ->get();
    }
}
