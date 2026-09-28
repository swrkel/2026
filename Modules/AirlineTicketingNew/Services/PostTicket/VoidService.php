<?php

namespace Modules\AirlineTicketingNew\Services\PostTicket;

use Illuminate\Support\Facades\DB;
use Modules\AirlineTicketingNew\Entities\Ticket;
use Modules\AirlineTicketingNew\Entities\TicketVoid;

class VoidService
{
    public function __construct(
        private readonly TicketActionNumberService $numbers,
        private readonly TicketHistoryService $history
    ) {
    }

    public function request(Ticket $ticket, array $data): TicketVoid
    {
        return DB::transaction(function () use ($ticket, $data): TicketVoid {
            $record = TicketVoid::query()->create([
                'business_id' => $ticket->business_id,
                'business_location_id' => $ticket->business_location_id,
                'store_id' => $ticket->store_id,
                'void_no' => $this->numbers->next(
                    $ticket->business_id,
                    $ticket->business_location_id,
                    $ticket->store_id,
                    'void'
                ),
                'ticket_id' => $ticket->id,
                'request_date' => $data['request_date'],
                'reason' => $data['reason'],
                'status' => 'pending',
                'requested_by' => auth()->id(),
                'remarks' => $data['remarks'] ?? null,
            ]);

            $this->history->record($ticket, 'void_requested', TicketVoid::class, $record->id, $ticket->status, $ticket->status, $record->reason);

            return $record;
        });
    }

    public function approve(TicketVoid $void): TicketVoid
    {
        return DB::transaction(function () use ($void): TicketVoid {
            $ticket = Ticket::query()->findOrFail($void->ticket_id);
            $from = $ticket->status;

            $void->update([
                'status' => 'approved',
                'approved_by' => auth()->id(),
                'processed_by' => auth()->id(),
                'void_date' => now()->toDateString(),
            ]);

            $ticket->update(['status' => 'voided']);

            $this->history->record($ticket, 'void_approved', TicketVoid::class, $void->id, $from, 'voided', $void->reason);

            return $void->refresh();
        });
    }
}
