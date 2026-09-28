<?php

namespace Modules\Subscription\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class SubscriptionExpiredNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $subscriptionData;

    public function __construct($subscriptionData)
    {
        $this->subscriptionData = $subscriptionData;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'subscription_list_id' => $this->subscriptionData['subscription_list_id'],
            'customer_name' => $this->subscriptionData['customer_name'],
            'expiry_date' => $this->subscriptionData['expiry_date'],
        ];
    }
}
