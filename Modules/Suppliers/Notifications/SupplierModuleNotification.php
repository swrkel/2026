<?php

namespace Modules\Suppliers\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SupplierModuleNotification extends Notification
{
    use Queueable;

    protected string $message;
    protected array $data;

    public function __construct(string $message, array $data = [])
    {
        $this->message = $message;
        $this->data = $data;
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return array_merge(['message' => $this->message], $this->data);
    }
}
