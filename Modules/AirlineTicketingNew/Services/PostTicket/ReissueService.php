<?php

namespace Modules\AirlineTicketingNew\Services\PostTicket;

use Illuminate\Support\Facades\DB;
use Modules\AirlineTicketingNew\Entities\Ticket;
use Modules\AirlineTicketingNew\Entities\TicketReissue;

class ReissueService
{
    public function __construct(
        private readonly TicketActionNumberService $numbers,
        private readonly TicketHistoryService $history
    ) {
    }

    public function request(Ticket $ticket, array $data): TicketReissue
    {
        return DB::transaction(function () use ($ticket, $data): TicketReissue {
            $total = round(
                (float) ($data['fare_difference'] ?? 0)
                + (float) ($data['tax_difference'] ?? 0)
                + (float) ($data['service_fee'] ?? 0)
                + (float) ($data['penalty_amount'] ?? 0),
                4
            );

            $record = TicketReissue::query()->create([
                'business_id' => $ticket->business_id,
                'business_location_id' => $ticket->business_location_id,
                'store_id' => $ticket->store_id,
                'reissue_no' => $this->numbers->next(
                    $ticket->business_id,
                    $ticket->business_location_id,
                    $ticket->store_id,
                    'reissue'
                ),
                'original_ticket_id' => $ticket->id,
                'reservation_id' => $ticket->reservation_id,
                'request_date' => $data['request_date'],
                'reason' => $data['reason'],
                'fare_difference' => $data['fare_difference'] ?? 0,
                'tax_difference' => $data['tax_difference'] ?? 0,
                'service_fee' => $data['service_fee'] ?? 0,
                'penalty_amount' => $data['penalty_amount'] ?? 0,
                'total_collectable' => $total,
                'status' => 'pending',
                'requested_by' => auth()->id(),
                'remarks' => $data['remarks'] ?? null,
            ]);

            $this->history->record($ticket, 'reissue_requested', TicketReissue::class, $record->id, $ticket->status, $ticket->status, $record->reason, $total);

            return $record;
        });
    }
}
