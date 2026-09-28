<?php
namespace Modules\AirlineTicketingNew\Services\Ancillary;

use Modules\AirlineTicketingNew\Entities\AncillaryBooking;
use Modules\AirlineTicketingNew\Entities\AncillaryService;
use Modules\AirlineTicketingNew\Support\TravelServices\ServiceNumber;

class AncillaryBookingService
{
    public function __construct(private readonly ServiceNumber $numbers)
    {
    }

    public function create(AncillaryService $service, array $data): AncillaryBooking
    {
        $quantity = (float) ($data['quantity'] ?? 1);
        $total = round((float) $service->sale_amount * $quantity, 4);

        return AncillaryBooking::query()->create(array_merge($data, [
            'business_id' => $service->business_id,
            'business_location_id' => $service->business_location_id,
            'store_id' => $service->store_id,
            'booking_no' => $this->numbers->next([
                'business_id' => $service->business_id,
                'business_location_id' => $service->business_location_id,
                'store_id' => $service->store_id,
            ], 'ancillary_booking', 'ATAB'),
            'ancillary_service_id' => $service->id,
            'unit_price' => $service->sale_amount,
            'total_amount' => $total,
            'status' => 'confirmed',
        ]));
    }
}
