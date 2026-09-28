<?php
namespace Modules\AirlineTicketingNew\Services\PostTicket;

use Illuminate\Support\Facades\DB;
use Modules\AirlineTicketingNew\Entities\Ticket;
use Modules\AirlineTicketingNew\Entities\TicketExchange;
use Modules\AirlineTicketingNew\Services\Transactions\DocumentNumberService;

class TicketExchangeService
{
    public function __construct(private readonly DocumentNumberService $numbers)
    {
    }

    public function exchange(Ticket $original, Ticket $replacement, array $data): TicketExchange
    {
        return DB::transaction(function () use ($original, $replacement, $data) {
            $difference = round((float)$replacement->grand_total - (float)$original->grand_total, 4);

            $record = TicketExchange::query()->create([
                'business_id' => $original->business_id,
                'business_location_id' => $original->business_location_id,
                'store_id' => $original->store_id,
                'exchange_no' => $this->numbers->next(
                    $original->business_id,
                    $original->business_location_id,
                    $original->store_id,
                    'ticket_exchange',
                    'ATEX'
                ),
                'original_ticket_id' => $original->id,
                'replacement_ticket_id' => $replacement->id,
                'exchange_date' => $data['exchange_date'],
                'original_value' => $original->grand_total,
                'new_value' => $replacement->grand_total,
                'exchange_difference' => $difference,
                'status' => 'completed',
                'remarks' => $data['remarks'] ?? null,
                'created_by' => auth()->id(),
            ]);

            $original->update(['status' => 'exchanged']);
            $replacement->update(['ticket_type' => 'exchange']);

            return $record;
        });
    }
}
