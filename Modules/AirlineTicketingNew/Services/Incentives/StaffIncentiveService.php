<?php

namespace Modules\AirlineTicketingNew\Services\Incentives;

use Modules\AirlineTicketingNew\Entities\StaffIncentive;
use Modules\AirlineTicketingNew\Entities\Ticket;
use Modules\AirlineTicketingNew\Services\Transactions\DocumentNumberService;

class StaffIncentiveService
{
    public function __construct(private readonly DocumentNumberService $numbers)
    {
    }

    public function create(Ticket $ticket, array $data): StaffIncentive
    {
        $amount = $data['calculation_type'] === 'percentage'
            ? round((float)$data['basis_amount'] * (float)$data['rate'] / 100, 4)
            : round((float)$data['rate'], 4);

        return StaffIncentive::query()->create([
            'business_id' => $ticket->business_id,
            'business_location_id' => $ticket->business_location_id,
            'store_id' => $ticket->store_id,
            'incentive_no' => $this->numbers->next(
                $ticket->business_id,
                $ticket->business_location_id,
                $ticket->store_id,
                'staff_incentive',
                'ATSI'
            ),
            'user_id' => $data['user_id'],
            'ticket_id' => $ticket->id,
            'invoice_id' => $data['invoice_id'] ?? null,
            'incentive_date' => $data['incentive_date'],
            'calculation_type' => $data['calculation_type'],
            'basis_amount' => $data['basis_amount'],
            'rate' => $data['rate'],
            'incentive_amount' => $amount,
            'paid_amount' => 0,
            'due_amount' => $amount,
            'status' => 'unpaid',
            'remarks' => $data['remarks'] ?? null,
        ]);
    }
}
