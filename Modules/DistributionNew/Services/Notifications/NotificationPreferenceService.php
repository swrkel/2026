<?php

namespace Modules\DistributionNew\Services\Notifications;

use Modules\DistributionNew\Models\DisnewNotificationPreference;

class NotificationPreferenceService
{
    public function save(int $businessId, string $eventKey, array $data): DisnewNotificationPreference
    {
        return DisnewNotificationPreference::updateOrCreate(
            ['business_id' => $businessId, 'event_key' => $eventKey, 'user_id' => $data['user_id'] ?? null],
            [
                'business_location_id' => $data['business_location_id'] ?? null,
                'sms_enabled' => (bool)($data['sms_enabled'] ?? true),
                'email_enabled' => (bool)($data['email_enabled'] ?? false),
                'in_app_enabled' => (bool)($data['in_app_enabled'] ?? true),
                'officer_group_key' => $data['officer_group_key'] ?? null,
                'updated_by' => $data['updated_by'] ?? null,
            ]
        );
    }
}
