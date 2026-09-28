<?php

namespace Modules\AirlineTicketingNew\Services\Transactions;

use Illuminate\Support\Facades\DB;
use Modules\AirlineTicketingNew\Entities\Quotation;
use Modules\AirlineTicketingNew\Entities\Reservation;
use Modules\AirlineTicketingNew\Entities\ReservationStatusHistory;

class ReservationService
{
    public function __construct(private readonly DocumentNumberService $numbers)
    {
    }

    public function createFromQuotation(Quotation $quotation, array $data): Reservation
    {
        return DB::transaction(function () use ($quotation, $data): Reservation {
            $reservation = Reservation::query()->create([
                'business_id' => $quotation->business_id,
                'business_location_id' => $quotation->business_location_id,
                'store_id' => $quotation->store_id,
                'reservation_no' => $this->numbers->next(
                    $quotation->business_id,
                    $quotation->business_location_id,
                    $quotation->store_id,
                    'reservation',
                    'ATR'
                ),
                'pnr_code' => $data['pnr_code'] ?? null,
                'reservation_date' => $data['reservation_date'] ?? now()->toDateString(),
                'ticketing_deadline' => $data['ticketing_deadline'] ?? null,
                'quotation_id' => $quotation->id,
                'customer_type' => $quotation->customer_type,
                'corporate_customer_id' => $quotation->corporate_customer_id,
                'passenger_id' => $quotation->passenger_id,
                'agent_id' => $quotation->agent_id,
                'supplier_id' => $data['supplier_id'] ?? null,
                'currency_code' => $quotation->currency_code,
                'exchange_rate' => $quotation->exchange_rate,
                'subtotal' => $quotation->subtotal,
                'tax_total' => $quotation->tax_total,
                'service_fee_total' => $quotation->service_fee_total,
                'discount_total' => $quotation->discount_total,
                'grand_total' => $quotation->grand_total,
                'paid_total' => 0,
                'due_total' => $quotation->grand_total,
                'status' => 'reserved',
                'source' => 'quotation',
                'remarks' => $data['remarks'] ?? $quotation->remarks,
            ]);

            foreach ($quotation->segments as $segment) {
                $reservation->segments()->create([
                    'business_id' => $reservation->business_id,
                    'business_location_id' => $reservation->business_location_id,
                    'store_id' => $reservation->store_id,
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
                    'supplier_id' => $data['supplier_id'] ?? null,
                    'base_fare' => $segment->base_fare,
                    'tax_amount' => $segment->tax_amount,
                    'service_fee' => $segment->service_fee,
                    'discount_amount' => $segment->discount_amount,
                    'segment_total' => $segment->segment_total,
                    'segment_status' => 'reserved',
                ]);
            }

            if ($quotation->passenger_id) {
                $reservation->passengers()->create([
                    'business_id' => $reservation->business_id,
                    'business_location_id' => $reservation->business_location_id,
                    'store_id' => $reservation->store_id,
                    'passenger_id' => $quotation->passenger_id,
                    'passenger_type' => 'adult',
                    'status' => 'active',
                ]);
            }

            ReservationStatusHistory::query()->create([
                'business_id' => $reservation->business_id,
                'business_location_id' => $reservation->business_location_id,
                'store_id' => $reservation->store_id,
                'reservation_id' => $reservation->id,
                'from_status' => null,
                'to_status' => 'reserved',
                'reason' => 'Created from quotation ' . $quotation->quotation_no,
                'changed_by' => auth()->id(),
                'changed_at' => now(),
            ]);

            $quotation->update(['status' => 'converted']);

            return $reservation->fresh(['segments', 'passengers']);
        });
    }

    public function changeStatus(Reservation $reservation, string $status, ?string $reason = null): Reservation
    {
        return DB::transaction(function () use ($reservation, $status, $reason): Reservation {
            $from = $reservation->status;
            $reservation->update(['status' => $status]);

            ReservationStatusHistory::query()->create([
                'business_id' => $reservation->business_id,
                'business_location_id' => $reservation->business_location_id,
                'store_id' => $reservation->store_id,
                'reservation_id' => $reservation->id,
                'from_status' => $from,
                'to_status' => $status,
                'reason' => $reason,
                'changed_by' => auth()->id(),
                'changed_at' => now(),
            ]);

            return $reservation->refresh();
        });
    }
}
