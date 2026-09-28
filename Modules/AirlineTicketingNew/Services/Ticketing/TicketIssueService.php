<?php

namespace Modules\AirlineTicketingNew\Services\Ticketing;

use Illuminate\Support\Facades\DB;
use Modules\AirlineTicketingNew\Entities\Reservation;
use Modules\AirlineTicketingNew\Entities\Ticket;
use Modules\AirlineTicketingNew\Services\Transactions\DocumentNumberService;
use Modules\AirlineTicketingNew\Services\Transactions\ReservationService;

class TicketIssueService
{
    public function __construct(
        private readonly DocumentNumberService $numbers,
        private readonly ReservationService $reservationService
    ) {
    }

    public function issue(Reservation $reservation, array $data): Ticket
    {
        return DB::transaction(function () use ($reservation, $data): Ticket {
            $ticket = Ticket::query()->create([
                'business_id' => $reservation->business_id,
                'business_location_id' => $reservation->business_location_id,
                'store_id' => $reservation->store_id,
                'ticket_no' => $data['ticket_no'] ?: $this->numbers->next(
                    $reservation->business_id,
                    $reservation->business_location_id,
                    $reservation->store_id,
                    'ticket',
                    'ATT'
                ),
                'reservation_id' => $reservation->id,
                'reservation_passenger_id' => $data['reservation_passenger_id'] ?? null,
                'passenger_id' => $data['passenger_id'] ?? $reservation->passenger_id,
                'airline_id' => $data['airline_id'] ?? optional($reservation->segments->first())->airline_id,
                'supplier_id' => $data['supplier_id'] ?? $reservation->supplier_id,
                'issue_date' => $data['issue_date'],
                'ticketing_agent_id' => auth()->id(),
                'currency_code' => $reservation->currency_code,
                'exchange_rate' => $reservation->exchange_rate,
                'base_fare' => $reservation->subtotal,
                'tax_total' => $reservation->tax_total,
                'service_fee_total' => $reservation->service_fee_total,
                'discount_total' => $reservation->discount_total,
                'grand_total' => $reservation->grand_total,
                'status' => 'issued',
                'ticket_type' => $data['ticket_type'] ?? 'normal',
                'remarks' => $data['remarks'] ?? null,
            ]);

            foreach ($reservation->segments as $segment) {
                $ticket->segments()->create([
                    'business_id' => $ticket->business_id,
                    'business_location_id' => $ticket->business_location_id,
                    'store_id' => $ticket->store_id,
                    'reservation_segment_id' => $segment->id,
                    'segment_no' => $segment->segment_no,
                    'airline_id' => $segment->airline_id,
                    'flight_number' => $segment->flight_number,
                    'origin_airport_id' => $segment->origin_airport_id,
                    'destination_airport_id' => $segment->destination_airport_id,
                    'departure_at' => $segment->departure_at,
                    'arrival_at' => $segment->arrival_at,
                    'travel_class_id' => $segment->travel_class_id,
                    'booking_class' => $segment->booking_class,
                    'fare_basis' => $segment->fare_basis,
                    'baggage_allowance' => $segment->baggage_allowance,
                    'coupon_status' => 'open',
                ]);
            }

            $this->reservationService->changeStatus($reservation, 'ticketed', 'Ticket issued: ' . $ticket->ticket_no);

            return $ticket->fresh(['segments']);
        });
    }
}
