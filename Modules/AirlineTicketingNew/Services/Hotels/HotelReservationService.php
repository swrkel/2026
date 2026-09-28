<?php

namespace Modules\AirlineTicketingNew\Services\Hotels;

use Modules\AirlineTicketingNew\Entities\HotelReservation;
use Modules\AirlineTicketingNew\Services\Transactions\DocumentNumberService;

class HotelReservationService
{
    public function __construct(private readonly DocumentNumberService $numbers)
    {
    }

    public function create(array $data): HotelReservation
    {
        return HotelReservation::query()->create(array_merge($data, [
            'reservation_no' => $this->numbers->next(
                $data['business_id'],
                $data['business_location_id'] ?? null,
                $data['store_id'] ?? null,
                'hotel_reservation',
                'ATHR'
            ),
            'voucher_no' => $this->numbers->next(
                $data['business_id'],
                $data['business_location_id'] ?? null,
                $data['store_id'] ?? null,
                'hotel_voucher',
                'ATHV'
            ),
            'paid_amount' => 0,
            'due_amount' => $data['sale_amount'],
            'status' => 'confirmed',
        ]));
    }
}
