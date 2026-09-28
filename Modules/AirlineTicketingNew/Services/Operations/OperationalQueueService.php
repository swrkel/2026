<?php

namespace Modules\AirlineTicketingNew\Services\Operations;

use Illuminate\Support\Facades\DB;
use Modules\AirlineTicketingNew\Entities\OperationalTask;

class OperationalQueueService
{
    public function rebuild(int $businessId): int
    {
        return DB::transaction(function () use ($businessId): int {
            $count = 0;

            $reservations = DB::table('atn_reservations')
                ->where('business_id', $businessId)
                ->whereIn('status', ['reserved','confirmed','on_hold'])
                ->whereNotNull('ticketing_deadline')
                ->where('ticketing_deadline', '<=', now()->addDay())
                ->get();

            foreach ($reservations as $reservation) {
                OperationalTask::query()->updateOrCreate(
                    [
                        'business_id' => $businessId,
                        'task_type' => 'ticketing_deadline',
                        'reference_type' => 'reservation',
                        'reference_id' => $reservation->id,
                    ],
                    [
                        'business_location_id' => $reservation->business_location_id,
                        'store_id' => $reservation->store_id,
                        'task_no' => 'ATOP-' . $reservation->id,
                        'title' => 'Ticketing deadline: ' . $reservation->reservation_no,
                        'priority' => 'high',
                        'due_at' => $reservation->ticketing_deadline,
                        'status' => 'open',
                    ]
                );
                $count++;
            }

            return $count;
        });
    }
}
