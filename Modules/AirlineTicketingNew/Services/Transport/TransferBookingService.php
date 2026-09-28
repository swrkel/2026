<?php

namespace Modules\AirlineTicketingNew\Services\Transport;

use Modules\AirlineTicketingNew\Entities\TransferBooking;
use Modules\AirlineTicketingNew\Services\Transactions\DocumentNumberService;

class TransferBookingService
{
    public function __construct(private readonly DocumentNumberService $numbers)
    {
    }

    public function create(array $data): TransferBooking
    {
        return TransferBooking::query()->create(array_merge($data, [
            'booking_no' => $this->numbers->next(
                $data['business_id'],
                $data['business_location_id'] ?? null,
                $data['store_id'] ?? null,
                'transfer_booking',
                'ATTF'
            ),
            'paid_amount' => 0,
            'due_amount' => $data['sale_amount'],
            'status' => 'confirmed',
        ]));
    }
}
