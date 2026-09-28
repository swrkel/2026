<?php

namespace Modules\BeautySaloons\Services;

class AppointmentService
{
    public function calculateEndTime(string $startTime, int $durationMinutes): string
    {
        return date('H:i', strtotime($startTime . ' +' . $durationMinutes . ' minutes'));
    }
}
