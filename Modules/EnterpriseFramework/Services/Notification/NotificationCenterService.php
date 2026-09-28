<?php

namespace Modules\EnterpriseFramework\Services\Notification;

class NotificationCenterService
{
    public function all(): array
    {
        return [
            ['type' => 'info', 'module' => 'Enterprise Framework', 'title' => 'Notification Center Ready', 'message' => 'Modules can publish reporting alerts here.'],
        ];
    }
}
