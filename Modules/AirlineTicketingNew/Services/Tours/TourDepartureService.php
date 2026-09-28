<?php
namespace Modules\AirlineTicketingNew\Services\Tours;

use Illuminate\Validation\ValidationException;
use Modules\AirlineTicketingNew\Entities\TourDeparture;

class TourDepartureService
{
    public function assertCapacity(TourDeparture $departure, int $passengers): void
    {
        $remaining = max(0, (int) $departure->capacity - (int) $departure->booked_count);

        if (!$departure->is_open || $remaining < $passengers) {
            throw ValidationException::withMessages([
                'passenger_count' => 'Tour departure capacity is insufficient.',
            ]);
        }
    }
}
