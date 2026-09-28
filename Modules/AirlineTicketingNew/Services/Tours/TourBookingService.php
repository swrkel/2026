<?php

namespace Modules\AirlineTicketingNew\Services\Tours;

use Modules\AirlineTicketingNew\Entities\TourBooking;
use Modules\AirlineTicketingNew\Entities\TourPackage;
use Modules\AirlineTicketingNew\Services\Transactions\DocumentNumberService;

class TourBookingService
{
    public function __construct(private readonly DocumentNumberService $numbers)
    {
    }

    public function create(TourPackage $package, array $data): TourBooking
    {
        $gross = round((float)$package->sale_amount * (int)$data['passenger_count'], 4);
        $discount = (float)($data['discount_amount'] ?? 0);
        $net = max(0, round($gross - $discount, 4));

        return TourBooking::query()->create([
            'business_id' => $package->business_id,
            'business_location_id' => $package->business_location_id,
            'store_id' => $package->store_id,
            'booking_no' => $this->numbers->next(
                $package->business_id,
                $package->business_location_id,
                $package->store_id,
                'tour_booking',
                'ATTB'
            ),
            'tour_package_id' => $package->id,
            'departure_date' => $data['departure_date'],
            'return_date' => $data['return_date'],
            'passenger_count' => $data['passenger_count'],
            'currency_code' => $package->currency_code,
            'gross_amount' => $gross,
            'discount_amount' => $discount,
            'net_amount' => $net,
            'paid_amount' => 0,
            'due_amount' => $net,
            'status' => 'confirmed',
            'remarks' => $data['remarks'] ?? null,
        ]);
    }
}
