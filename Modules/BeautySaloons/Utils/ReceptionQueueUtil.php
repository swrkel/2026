<?php

namespace Modules\BeautySaloons\Utils;

class ReceptionQueueUtil
{
    public function statusLabels(): array
    {
        return [
            'waiting' => 'Waiting',
            'checked_in' => 'Checked In',
            'in_service' => 'In Service',
            'on_hold' => 'On Hold',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
            'no_show' => 'No Show',
        ];
    }
}
