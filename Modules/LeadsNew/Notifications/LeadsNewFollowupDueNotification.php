<?php

namespace Modules\LeadsNew\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class LeadsNewFollowupDueNotification extends Notification
{
    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Leads-New Follow-up Due')
            ->line('A Leads-New follow-up is due. Please check the Leads-New dashboard.');
    }
}
