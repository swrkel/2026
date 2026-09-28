<?php

namespace Modules\MyHealthMembers\Services\Hospital;

use Modules\MyHealthMembers\Entities\MyHealthAppointment;

class MyHealthAppointmentNumberService
{
    public function next(): string
    {
        $last = MyHealthAppointment::orderBy('id', 'desc')->value('id');
        return 'APP-' . str_pad((int) $last + 1, 6, '0', STR_PAD_LEFT);
    }

    public function queueNumber(string $date): string
    {
        $count = MyHealthAppointment::whereDate('appointment_date', $date)->count() + 1;
        return str_pad($count, 3, '0', STR_PAD_LEFT);
    }
}
