<?php

namespace Modules\Subscription\Http\Controllers;

use App\User;

class DataController
{
    public function parse_notification($notification)
    {
        $notification_data = [];

        if ($notification->type == 'Modules\Subscription\Notifications\SubscriptionExpiredNotification') {
            $data = $notification->data;
            $msg = __('subscription::lang.subscription_expired_notification', [
                'customer_name' => $data['customer_name'] ?? '',
                'expiry_date' => $data['expiry_date'] ?? '',
            ]);

            $notification_data = [
                'msg' => $msg,
                'icon_class' => 'fa fa-exclamation-circle text-red',
                'link' => action('\Modules\Subscription\Http\Controllers\SubscriptionListController@index'),
                'read_at' => $notification->read_at,
                'created_at' => $notification->created_at->diffForHumans(),
            ];
        }

        return $notification_data;
    }
}
