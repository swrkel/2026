<?php

namespace Modules\BeautySaloons\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Modules\BeautySaloons\Entities\BeautyAppointment;
use Modules\BeautySaloons\Entities\BeautyAppointmentResource;
use Modules\BeautySaloons\Entities\BeautySchedulerSlot;
use Modules\BeautySaloons\Entities\BeautyWaitlist;

class AdvancedSchedulerService
{
    public function calendarEvents(array $filters = []): Collection
    {
        $query = BeautyAppointment::query();

        if (!empty($filters['business_location_id'])) {
            $query->where('business_location_id', $filters['business_location_id']);
        }

        if (!empty($filters['staff_id'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('staff_id', $filters['staff_id'])
                    ->orWhereExists(function ($sub) use ($filters) {
                        $sub->selectRaw('1')
                            ->from('bs_appointment_resources')
                            ->whereColumn('bs_appointment_resources.appointment_id', 'bs_appointments.id')
                            ->where('bs_appointment_resources.staff_id', $filters['staff_id']);
                    });
            });
        }

        if (!empty($filters['start'])) {
            $query->whereDate('appointment_date', '>=', Carbon::parse($filters['start'])->toDateString());
        }

        if (!empty($filters['end'])) {
            $query->whereDate('appointment_date', '<=', Carbon::parse($filters['end'])->toDateString());
        }

        return $query->orderBy('appointment_date')->orderBy('start_time')->get()->map(function ($appointment) {
            return [
                'id' => $appointment->id,
                'title' => trim(($appointment->appointment_no ?? 'Appointment') . ' - ' . ($appointment->status ?? 'booked')),
                'start' => trim(($appointment->appointment_date ?? now()->toDateString()) . ' ' . ($appointment->start_time ?? '00:00:00')),
                'end' => trim(($appointment->appointment_date ?? now()->toDateString()) . ' ' . ($appointment->end_time ?? $appointment->start_time ?? '00:30:00')),
                'status' => $appointment->status ?? 'booked',
                'url' => route('beautysaloons.appointments.edit', $appointment->id),
            ];
        });
    }

    public function isSlotAvailable(?int $staffId, ?int $roomId, string $date, string $startTime, string $endTime, ?int $ignoreAppointmentId = null): bool
    {
        $query = BeautyAppointmentResource::query()
            ->whereDate('starts_at', $date)
            ->where(function ($q) use ($staffId, $roomId) {
                if ($staffId) {
                    $q->orWhere('staff_id', $staffId);
                }
                if ($roomId) {
                    $q->orWhere('room_id', $roomId);
                }
            })
            ->where('status', '!=', 'cancelled')
            ->where(function ($q) use ($date, $startTime, $endTime) {
                $start = Carbon::parse($date . ' ' . $startTime);
                $end = Carbon::parse($date . ' ' . $endTime);
                $q->whereBetween('starts_at', [$start, $end])
                    ->orWhereBetween('ends_at', [$start, $end])
                    ->orWhere(function ($inner) use ($start, $end) {
                        $inner->where('starts_at', '<=', $start)->where('ends_at', '>=', $end);
                    });
            });

        if ($ignoreAppointmentId) {
            $query->where('appointment_id', '!=', $ignoreAppointmentId);
        }

        return !$query->exists();
    }

    public function reserveResource(int $appointmentId, array $data): BeautyAppointmentResource
    {
        return BeautyAppointmentResource::updateOrCreate(
            ['appointment_id' => $appointmentId, 'service_id' => $data['service_id'] ?? null],
            [
                'staff_id' => $data['staff_id'] ?? null,
                'room_id' => $data['room_id'] ?? null,
                'starts_at' => $data['starts_at'] ?? null,
                'ends_at' => $data['ends_at'] ?? null,
                'status' => $data['status'] ?? 'booked',
            ]
        );
    }

    public function addToWaitlist(array $data): BeautyWaitlist
    {
        return BeautyWaitlist::create($data + ['status' => 'waiting']);
    }

    public function createSlot(array $data): BeautySchedulerSlot
    {
        return BeautySchedulerSlot::create($data);
    }
}
