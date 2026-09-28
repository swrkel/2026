<?php

namespace Modules\AutoService\Services\Workflow;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ReminderEngineService
{
    public function createNextServiceReminder(int $vehicleId, ?int $jobId, ?string $dueDate, ?int $dueOdometer = null): void
    {
        if (!$dueDate) {
            return;
        }

        $vehicle = DB::table('auto_service_vehicles')->where('id', $vehicleId)->first();
        if (!$vehicle) {
            return;
        }

        $sendOn = Carbon::parse($dueDate)->subDays(7)->toDateString();

        DB::table('auto_service_reminders')->updateOrInsert(
            ['vehicle_id' => $vehicleId, 'job_id' => $jobId, 'due_date' => $dueDate, 'channel' => 'sms'],
            [
                'business_id' => $vehicle->business_id ?? null,
                'contact_id' => $vehicle->contact_id ?? null,
                'days_before' => 7,
                'send_on' => $sendOn,
                'message' => 'Your vehicle service is due on '.$dueDate.'.',
                'status' => 'pending',
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }
}
