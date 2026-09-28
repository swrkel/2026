<?php

namespace Modules\Finance\Services;

use Modules\Finance\Entities\FinanceNotification;

class FinanceNotificationService
{
    public static function create(
        $notification_type,
        $title,
        $message = null,
        $user_id = null,
        $priority = 'medium',
        $reference_type = null,
        $reference_id = null,
        $location_id = null
    ) {
        return FinanceNotification::create([
            'business_id' => session()->get('user.business_id'),
            'location_id' => $location_id,
            'user_id' => $user_id,
            'notification_type' => $notification_type,
            'title' => $title,
            'message' => $message,
            'reference_type' => $reference_type,
            'reference_id' => $reference_id,
            'priority' => $priority,
            'status' => 'unread',
        ]);
    }
}