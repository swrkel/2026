<?php

namespace Modules\Tailoring\Services;

class TailoringAppointmentService
{
    public function availableSlots(array $filters = []): array
    {
        return ['status' => 'ready', 'filters' => $filters];
    }

    public function book(array $filters = []): array
    {
        return ['status' => 'ready', 'filters' => $filters];
    }

    public function reschedule(array $filters = []): array
    {
        return ['status' => 'ready', 'filters' => $filters];
    }

}
