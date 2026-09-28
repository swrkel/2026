<?php

namespace Modules\AirlineTicketingNew\Services\PostTicket;

use Modules\AirlineTicketingNew\Entities\Ticket;
use Modules\AirlineTicketingNew\Entities\TicketActionHistory;

class TicketHistoryService
{
    public function record(
        Ticket $ticket,
        string $actionType,
        ?string $referenceType,
        ?int $referenceId,
        ?string $fromStatus,
        ?string $toStatus,
        ?string $reason,
        float $amount = 0
    ): void {
        TicketActionHistory::query()->create([
            'business_id' => $ticket->business_id,
            'business_location_id' => $ticket->business_location_id,
            'store_id' => $ticket->store_id,
            'ticket_id' => $ticket->id,
            'action_type' => $actionType,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'reason' => $reason,
            'amount' => $amount,
            'action_by' => auth()->id(),
            'action_at' => now(),
        ]);
    }
}
