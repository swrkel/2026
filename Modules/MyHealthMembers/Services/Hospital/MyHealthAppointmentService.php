<?php

namespace Modules\MyHealthMembers\Services\Hospital;

use Illuminate\Support\Arr;
use Modules\MyHealthMembers\Entities\MyHealthAppointment;

class MyHealthAppointmentService
{
    public function __construct(private MyHealthAppointmentNumberService $numberService)
    {
    }

    public function create(array $data): MyHealthAppointment
    {
        $date = Arr::get($data, 'appointment_date', now()->toDateString());
        $queueNo = $this->numberService->queueNumber($date);

        $payload = [
            'business_id' => Arr::get($data, 'business_id'),
            'appointment_no' => $this->numberService->next(),
            'member_id' => Arr::get($data, 'member_id'),
            'doctor_id' => Arr::get($data, 'doctor_id'),
            'department_id' => Arr::get($data, 'department_id'),
            'room_id' => Arr::get($data, 'room_id'),
            'appointment_date' => $date,
            'appointment_time' => Arr::get($data, 'appointment_time'),
            'queue_no' => $queueNo,
            'token_no' => 'T-' . $queueNo,
            'visit_type' => Arr::get($data, 'visit_type', 'opd'),
            'status' => Arr::get($data, 'status', 'waiting'),
            'reason' => Arr::get($data, 'reason'),
            'notes' => Arr::get($data, 'notes'),
            'created_by' => auth()->id(),
        ];

        return MyHealthAppointment::create($payload);
    }

    public function updateStatus(MyHealthAppointment $appointment, string $status): MyHealthAppointment
    {
        $appointment->update(['status' => $status]);
        return $appointment->fresh();
    }
}
