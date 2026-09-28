<?php

namespace Modules\MyHealthMembers\Services;

use Modules\MyHealthMembers\Entities\MyHealthTelemedicineAppointment;

class MyHealthAppointmentNumberService
{
    public function nextNumber(): string
    {
        $prefix = 'TEL-' . date('Ymd') . '-';
        $count = MyHealthTelemedicineAppointment::query()->where('appointment_no', 'like', $prefix . '%')->count() + 1;
        return $prefix . str_pad((string) $count, 5, '0', STR_PAD_LEFT);
    }
}
