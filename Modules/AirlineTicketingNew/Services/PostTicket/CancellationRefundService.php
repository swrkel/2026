<?php

namespace Modules\AirlineTicketingNew\Services\PostTicket;

use Illuminate\Support\Facades\DB;
use Modules\AirlineTicketingNew\Entities\CreditNote;
use Modules\AirlineTicketingNew\Entities\Refund;
use Modules\AirlineTicketingNew\Entities\Ticket;
use Modules\AirlineTicketingNew\Entities\TicketCancellation;

class CancellationRefundService
{
    public function __construct(
        private readonly TicketActionNumberService $numbers,
        private readonly TicketHistoryService $history
    ) {
    }

    public function requestCancellation(Ticket $ticket, array $data): TicketCancellation
    {
        return DB::transaction(function () use ($ticket, $data): TicketCancellation {
            $refundable = max(0, round((float) $ticket->grand_total - (float) ($data['cancellation_fee'] ?? 0), 4));

            $record = TicketCancellation::query()->create([
                'business_id' => $ticket->business_id,
                'business_location_id' => $ticket->business_location_id,
                'store_id' => $ticket->store_id,
                'cancellation_no' => $this->numbers->next(
                    $ticket->business_id,
                    $ticket->business_location_id,
                    $ticket->store_id,
                    'cancellation'
                ),
                'ticket_id' => $ticket->id,
                'request_date' => $data['request_date'],
                'reason' => $data['reason'],
                'cancellation_fee' => $data['cancellation_fee'] ?? 0,
                'refundable_amount' => $refundable,
                'status' => 'pending',
                'requested_by' => auth()->id(),
                'remarks' => $data['remarks'] ?? null,
            ]);

            $this->history->record($ticket, 'cancellation_requested', TicketCancellation::class, $record->id, $ticket->status, $ticket->status, $record->reason, $refundable);

            return $record;
        });
    }

    public function approveAndCreateRefund(TicketCancellation $cancellation, array $data): Refund
    {
        return DB::transaction(function () use ($cancellation, $data): Refund {
            $ticket = Ticket::query()->findOrFail($cancellation->ticket_id);
            $deductions = round(
                (float) $cancellation->cancellation_fee
                + (float) ($data['service_fee'] ?? 0)
                + (float) ($data['other_deductions'] ?? 0),
                4
            );
            $refundAmount = max(0, round((float) $ticket->grand_total - $deductions, 4));

            $cancellation->update([
                'status' => 'approved',
                'approved_by' => auth()->id(),
                'processed_by' => auth()->id(),
                'cancellation_date' => now()->toDateString(),
                'refundable_amount' => $refundAmount,
            ]);

            $refund = Refund::query()->create([
                'business_id' => $ticket->business_id,
                'business_location_id' => $ticket->business_location_id,
                'store_id' => $ticket->store_id,
                'refund_no' => $this->numbers->next(
                    $ticket->business_id,
                    $ticket->business_location_id,
                    $ticket->store_id,
                    'refund'
                ),
                'ticket_id' => $ticket->id,
                'invoice_id' => $data['invoice_id'] ?? null,
                'payment_id' => $data['payment_id'] ?? null,
                'cancellation_id' => $cancellation->id,
                'request_date' => now()->toDateString(),
                'approved_date' => now()->toDateString(),
                'currency_code' => $ticket->currency_code,
                'gross_amount' => $ticket->grand_total,
                'cancellation_fee' => $cancellation->cancellation_fee,
                'service_fee' => $data['service_fee'] ?? 0,
                'other_deductions' => $data['other_deductions'] ?? 0,
                'refund_amount' => $refundAmount,
                'refund_method' => $data['refund_method'] ?? 'credit_note',
                'reference_no' => $data['reference_no'] ?? null,
                'status' => 'approved',
                'requested_by' => $cancellation->requested_by,
                'approved_by' => auth()->id(),
                'remarks' => $data['remarks'] ?? null,
            ]);

            CreditNote::query()->create([
                'business_id' => $ticket->business_id,
                'business_location_id' => $ticket->business_location_id,
                'store_id' => $ticket->store_id,
                'credit_note_no' => $this->numbers->next(
                    $ticket->business_id,
                    $ticket->business_location_id,
                    $ticket->store_id,
                    'credit_note'
                ),
                'credit_note_date' => now()->toDateString(),
                'invoice_id' => $data['invoice_id'] ?? null,
                'refund_id' => $refund->id,
                'customer_type' => $data['customer_type'] ?? 'individual',
                'corporate_customer_id' => $data['corporate_customer_id'] ?? null,
                'passenger_id' => $ticket->passenger_id,
                'currency_code' => $ticket->currency_code,
                'amount' => $refundAmount,
                'reason' => $cancellation->reason,
                'status' => 'issued',
            ]);

            $from = $ticket->status;
            $ticket->update(['status' => 'cancelled']);

            $this->history->record($ticket, 'cancellation_approved', TicketCancellation::class, $cancellation->id, $from, 'cancelled', $cancellation->reason, $refundAmount);

            return $refund;
        });
    }
}
