<?php
namespace Modules\AirlineTicketingNew\Services\FlightOperations;

use Illuminate\Support\Facades\DB;
use Modules\AirlineTicketingNew\Entities\FlightDisruption;
use Modules\AirlineTicketingNew\Entities\OperationalTask;

class FlightDisruptionService
{
    public function report(array $data): FlightDisruption
    {
        return DB::transaction(function () use ($data) {
            $disruption = FlightDisruption::query()->create(array_merge($data, [
                'status' => 'open',
                'reported_at' => now(),
            ]));

            OperationalTask::query()->create([
                'business_id' => $disruption->business_id,
                'business_location_id' => $disruption->business_location_id,
                'store_id' => $disruption->store_id,
                'task_no' => 'ATFD-' . $disruption->id,
                'task_type' => 'flight_disruption',
                'reference_type' => FlightDisruption::class,
                'reference_id' => $disruption->id,
                'title' => 'Flight disruption: ' . $disruption->flight_number,
                'description' => $disruption->description,
                'priority' => 'urgent',
                'due_at' => now(),
                'status' => 'open',
            ]);

            return $disruption;
        });
    }
}
