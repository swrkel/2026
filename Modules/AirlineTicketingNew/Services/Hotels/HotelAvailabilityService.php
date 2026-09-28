<?php
namespace Modules\AirlineTicketingNew\Services\Hotels;

use Illuminate\Validation\ValidationException;
use Modules\AirlineTicketingNew\Entities\HotelAvailability;

class HotelAvailabilityService
{
    public function assertAvailable(int $businessId, int $hotelId, int $roomTypeId, string $from, string $to, int $rooms): void
    {
        $days = collect(\Carbon\CarbonPeriod::create($from, \Carbon\Carbon::parse($to)->subDay()));

        foreach ($days as $day) {
            $available = (int) HotelAvailability::query()
                ->where('business_id', $businessId)
                ->where('hotel_id', $hotelId)
                ->where('room_type_id', $roomTypeId)
                ->whereDate('availability_date', $day->toDateString())
                ->value('available_rooms');

            if ($available < $rooms) {
                throw ValidationException::withMessages([
                    'room_count' => 'Hotel room availability is insufficient for ' . $day->toDateString(),
                ]);
            }
        }
    }
}
