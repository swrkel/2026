<?php

namespace Modules\BeautySaloons\Reports;

use Modules\BeautySaloons\Entities\BeautyAppointment;

class AppointmentSchedulerReport
{
    public function summary(array $filters = [])
    {
        $query = BeautyAppointment::query();
        if (!empty($filters['from'])) {
            $query->whereDate('appointment_date', '>=', $filters['from']);
        }
        if (!empty($filters['to'])) {
            $query->whereDate('appointment_date', '<=', $filters['to']);
        }
        return [
            'total_appointments' => (clone $query)->count(),
            'completed' => (clone $query)->where('status', 'completed')->count(),
            'cancelled' => (clone $query)->where('status', 'cancelled')->count(),
        ];
    }
}
