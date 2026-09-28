<?php

namespace Modules\RestaurantNew\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\RestaurantNew\Entities\DiningTable;
use Modules\RestaurantNew\Entities\Reservation;

class ReservationService
{
    public function __construct(
        private TenantScopeService $scope,
        private NumberService $numbers,
        private AuditService $audit
    ) {}

    public function create(array $data): Reservation
    {
        $businessId = $this->scope->businessId();
        abort_unless($businessId, 403);

        $locationId = (int) ($data['location_id'] ?? 0) ?: $this->scope->currentLocationId();
        $tableId = (int) ($data['table_id'] ?? 0) ?: null;
        $table = null;

        if ($tableId) {
            $table = DiningTable::withoutGlobalScopes()
                ->where('business_id', $businessId)
                ->whereKey($tableId)
                ->where('is_active', true)
                ->first();
            if (! $table) {
                throw ValidationException::withMessages(['table_id' => 'The selected table is unavailable.']);
            }
            $locationId ??= (int) $table->location_id ?: null;
            if ($table->location_id && (int) $table->location_id !== (int) $locationId) {
                throw ValidationException::withMessages([
                    'table_id' => 'The selected table is not available at this location.',
                ]);
            }
            if ((int) $table->capacity < (int) $data['guest_count']) {
                throw ValidationException::withMessages([
                    'guest_count' => 'The selected table does not have enough capacity.',
                ]);
            }
        }

        abort_unless($locationId, 422, 'A business location is required for the reservation.');
        $this->scope->assertLocationAccess($locationId);

        $reservedAt = Carbon::parse($data['reserved_at']);
        $duration = max(15, (int) ($data['duration_minutes'] ?? 90));
        $reservationEnds = $reservedAt->copy()->addMinutes($duration);

        if ($tableId) {
            $conflict = Reservation::withoutGlobalScopes()
                ->where('business_id', $businessId)
                ->where('location_id', $locationId)
                ->where('table_id', $tableId)
                ->whereIn('status', ['booked', 'confirmed', 'seated'])
                ->where('reserved_at', '<', $reservationEnds)
                ->whereRaw('DATE_ADD(reserved_at, INTERVAL duration_minutes MINUTE) > ?', [$reservedAt])
                ->exists();
            if ($conflict) {
                throw ValidationException::withMessages([
                    'table_id' => 'The selected table already has an overlapping reservation.',
                ]);
            }
        }

        $reservation = Reservation::withoutGlobalScopes()->create([
            'business_id' => $businessId,
            'location_id' => $locationId,
            'table_id' => $tableId,
            'reservation_no' => $this->numbers->next($businessId, 'reservation', 'RSV-'),
            'customer_name' => $data['customer_name'],
            'customer_phone' => $data['customer_phone'],
            'customer_email' => $data['customer_email'] ?? null,
            'guest_count' => (int) $data['guest_count'],
            'reserved_at' => $reservedAt,
            'duration_minutes' => $duration,
            'status' => 'booked',
            'source' => $data['source'] ?? 'phone',
            'deposit_amount' => (float) ($data['deposit_amount'] ?? 0),
            'notes' => $data['notes'] ?? null,
            'created_by' => auth()->id(),
        ]);
        $this->audit->record(
            'reservation.created',
            'reservation',
            $reservation->id,
            [],
            ['reservation_no' => $reservation->reservation_no]
        );

        return $reservation;
    }

    public function updateStatus(Reservation $reservation, string $status, ?string $reason = null): Reservation
    {
        $businessId = $this->scope->businessId();
        $this->scope->assertBusinessRecord($reservation, $businessId);

        return DB::transaction(function () use ($reservation, $status, $reason, $businessId) {
            $locked = Reservation::withoutGlobalScopes()
                ->where('business_id', $businessId)
                ->whereKey($reservation->id)
                ->lockForUpdate()
                ->firstOrFail();
            $this->scope->assertLocationAccess((int) $locked->location_id);

            $transitions = [
                'booked' => ['confirmed', 'seated', 'cancelled', 'no_show'],
                'confirmed' => ['seated', 'cancelled', 'no_show'],
                'seated' => ['completed', 'cancelled'],
                'completed' => [],
                'cancelled' => [],
                'no_show' => [],
            ];
            if ($status === $locked->status) {
                return $locked;
            }
            if (! in_array($status, $transitions[$locked->status] ?? [], true)) {
                throw ValidationException::withMessages([
                    'status' => 'Invalid reservation status transition.',
                ]);
            }

            $changes = ['status' => $status];
            if ($status === 'confirmed') {
                $changes['confirmed_at'] = now();
            }
            if ($status === 'seated') {
                $changes['seated_at'] = now();
            }
            if ($status === 'cancelled') {
                $changes['cancelled_at'] = now();
            }
            if ($reason) {
                $changes['notes'] = trim(
                    ($locked->notes ? $locked->notes."\n" : '')
                    .ucwords(str_replace('_', ' ', $status)).': '.$reason
                );
            }
            $locked->update($changes);

            $this->audit->record(
                'reservation.status_changed',
                'reservation',
                $locked->id,
                [],
                ['status' => $status, 'reason' => $reason]
            );

            return $locked->fresh();
        }, 3);
    }
}
