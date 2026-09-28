<?php

namespace Modules\RiceMill\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * In-app/database notification for users permitted to approve Rice Mill sales.
 * It intentionally does not queue, so the message is available immediately
 * after the draft Sales Invoice has been saved.
 */
class SalesInvoiceApprovalRequired extends Notification
{
    use Queueable;

    public function __construct(private array $payload)
    {
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        return $this->payload;
    }

    public function toArray($notifiable): array
    {
        return $this->payload;
    }
}
