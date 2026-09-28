<?php
namespace Modules\Tailoring\Services;
class TailoringNotificationService
{
    public function templates(): array
    {
        return ['order_confirmed','trial_scheduled','ready_for_delivery','delivered','payment_reminder'];
    }
}
