<?php

namespace Modules\BeautySaloons\Services;

use Modules\BeautySaloons\Entities\BeautyAppointment;

class BeautyAppointmentCalendarService
{
    public function events(int $businessId, ?int $locationId = null): array
    {
        $query = BeautyAppointment::query()->where('business_id', $businessId);
        if ($locationId) {
            $query->where('business_location_id', $locationId);
        }
        return $query->latest('id')->limit(500)->get()->map(function ($appointment) {
            return [
                'id' => $appointment->id,
                'title' => $appointment->appointment_no ?? ('Appointment #' . $appointment->id),
                'start' => $appointment->start_datetime ?? $appointment->appointment_date ?? null,
                'end' => $appointment->end_datetime ?? null,
                'status' => $appointment->status ?? 'pending',
            ];
        })->toArray();
    }
}
