<?php

namespace Modules\Suppliers\Services\Infrastructure;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;

/**
 * Suppliers module notification gateway.
 *
 * Supplier business services should call this wrapper instead of using the
 * Laravel Notification facade directly. This keeps notification dispatching
 * replaceable and local to the Suppliers module.
 */
class SupplierNotificationService
{
    public function send($notifiables, $notification): void
    {
        Notification::send($notifiables, $notification);
    }

    public function sendNow($notifiables, $notification): void
    {
        Notification::sendNow($notifiables, $notification);
    }

    public function normalizeNotifiables($notifiables): Collection
    {
        return $notifiables instanceof Collection ? $notifiables : collect(is_array($notifiables) ? $notifiables : [$notifiables]);
    }
}
