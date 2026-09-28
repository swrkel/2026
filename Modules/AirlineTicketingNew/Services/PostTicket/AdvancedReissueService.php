<?php
namespace Modules\AirlineTicketingNew\Services\PostTicket;

use Illuminate\Support\Facades\DB;
use Modules\AirlineTicketingNew\Entities\ReissueQuote;
use Modules\AirlineTicketingNew\Entities\Ticket;
use Modules\AirlineTicketingNew\Services\Transactions\DocumentNumberService;

class AdvancedReissueService
{
    public function __construct(private readonly DocumentNumberService $numbers)
    {
    }

    public function quote(Ticket $ticket, array $data): ReissueQuote
    {
        $total = round(
            (float)($data['fare_difference'] ?? 0)
            + (float)($data['tax_difference'] ?? 0)
            + (float)($data['penalty_amount'] ?? 0)
            + (float)($data['service_fee'] ?? 0),
            4
        );

        return ReissueQuote::query()->create([
            'business_id' => $ticket->business_id,
            'business_location_id' => $ticket->business_location_id,
            'store_id' => $ticket->store_id,
            'quote_no' => $this->numbers->next(
                $ticket->business_id,
                $ticket->business_location_id,
                $ticket->store_id,
                'reissue_quote',
                'ATRQ'
            ),
            'ticket_id' => $ticket->id,
            'quoted_at' => now(),
            'fare_difference' => $data['fare_difference'] ?? 0,
            'tax_difference' => $data['tax_difference'] ?? 0,
            'penalty_amount' => $data['penalty_amount'] ?? 0,
            'service_fee' => $data['service_fee'] ?? 0,
            'total_collectable' => $total,
            'status' => 'quoted',
            'details_json' => $data['details_json'] ?? [],
            'created_by' => auth()->id(),
        ]);
    }
}
