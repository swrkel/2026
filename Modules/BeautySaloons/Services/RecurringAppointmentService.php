<?php

namespace Modules\BeautySaloons\Services;

use Carbon\Carbon;
use Modules\BeautySaloons\Entities\BeautyRecurringAppointment;

class RecurringAppointmentService
{
    public function store(array $data): BeautyRecurringAppointment
    {
        $data['next_run_date'] = $data['starts_on'] ?? now()->toDateString();
        return BeautyRecurringAppointment::create($data);
    }

    public function nextDate(BeautyRecurringAppointment $template): ?string
    {
        $date = Carbon::parse($template->next_run_date ?: $template->starts_on);

        if ($template->ends_on && $date->gt(Carbon::parse($template->ends_on))) {
            return null;
        }

        return $date->toDateString();
    }
}
