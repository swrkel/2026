<?php

namespace Modules\AirlineTicketingNew\Services\Ticketing;

use Illuminate\Support\Facades\DB;
use Modules\AirlineTicketingNew\Entities\Invoice;
use Modules\AirlineTicketingNew\Entities\Ticket;
use Modules\AirlineTicketingNew\Services\Transactions\DocumentNumberService;

class InvoiceService
{
    public function __construct(private readonly DocumentNumberService $numbers)
    {
    }

    public function createFromTicket(Ticket $ticket, array $data): Invoice
    {
        return DB::transaction(function () use ($ticket, $data): Invoice {
            return Invoice::query()->create([
                'business_id' => $ticket->business_id,
                'business_location_id' => $ticket->business_location_id,
                'store_id' => $ticket->store_id,
                'invoice_no' => $this->numbers->next(
                    $ticket->business_id,
                    $ticket->business_location_id,
                    $ticket->store_id,
                    'invoice',
                    'ATI'
                ),
                'invoice_date' => $data['invoice_date'],
                'reservation_id' => $ticket->reservation_id,
                'ticket_id' => $ticket->id,
                'customer_type' => $data['customer_type'],
                'corporate_customer_id' => $data['corporate_customer_id'] ?? null,
                'passenger_id' => $data['passenger_id'] ?? $ticket->passenger_id,
                'currency_code' => $ticket->currency_code,
                'exchange_rate' => $ticket->exchange_rate,
                'subtotal' => $ticket->base_fare,
                'tax_total' => $ticket->tax_total,
                'service_fee_total' => $ticket->service_fee_total,
                'discount_total' => $ticket->discount_total,
                'grand_total' => $ticket->grand_total,
                'paid_total' => 0,
                'due_total' => $ticket->grand_total,
                'status' => 'unpaid',
                'remarks' => $data['remarks'] ?? null,
            ]);
        });
    }
}
