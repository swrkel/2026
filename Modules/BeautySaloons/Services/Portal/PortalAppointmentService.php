<?php

namespace Modules\BeautySaloons\Services\Portal;

use Illuminate\Support\Arr;
use Modules\BeautySaloons\Entities\BeautyAppointment;

class PortalAppointmentService
{
    public function listForCustomer(int $customerId)
    {
        return BeautyAppointment::query()->where('customer_id', $customerId)->latest('id')->paginate(20);
    }

    public function createForCustomer(int $customerId, array $data): BeautyAppointment
    {
        $data['customer_id'] = $customerId;
        $data['status'] = $data['status'] ?? 'booked';
        return BeautyAppointment::create(Arr::only($data, (new BeautyAppointment())->getFillable()));
    }

    public function cancel(BeautyAppointment $appointment): BeautyAppointment
    {
        $appointment->status = 'cancelled';
        $appointment->save();
        return $appointment;
    }
}
