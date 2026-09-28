<?php

namespace Modules\RestaurantNew\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\RestaurantNew\Entities\Shift;

class ShiftService
{
    public function __construct(
        private TenantScopeService $scope,
        private NumberService $numbers,
        private AuditService $audit
    ) {}

    public function current(?int $locationId = null, ?int $userId = null): ?Shift
    {
        $businessId = $this->scope->businessId();
        if (! $businessId) {
            return null;
        }

        $query = Shift::withoutGlobalScopes()
            ->where('business_id', $businessId)
            ->where('user_id', $userId ?: auth()->id())
            ->where('status', 'open');

        if ($locationId) {
            $this->scope->assertLocationAccess($locationId);
            $query->where('location_id', $locationId);
        } else {
            $this->scope->applyLocationScope($query);
        }

        return $query->latest('id')->first();
    }

    public function open(array $data): Shift
    {
        $businessId = $this->scope->businessId();
        abort_unless($businessId, 403);

        $locationId = (int) ($data['location_id'] ?? 0) ?: null;
        abort_unless($locationId, 422, 'A business location is required.');
        $this->scope->assertLocationAccess($locationId);

        return DB::transaction(function () use ($data, $businessId, $locationId) {
            $existing = Shift::withoutGlobalScopes()
                ->where('business_id', $businessId)
                ->where('location_id', $locationId)
                ->where('user_id', auth()->id())
                ->where('status', 'open')
                ->lockForUpdate()
                ->first();
            if ($existing) {
                throw ValidationException::withMessages([
                    'shift' => 'You already have an open restaurant shift for this location.',
                ]);
            }

            $shift = Shift::withoutGlobalScopes()->create([
                'business_id' => $businessId,
                'location_id' => $locationId,
                'shift_no' => $this->numbers->next($businessId, 'shift', 'RSH-'),
                'user_id' => auth()->id(),
                'status' => 'open',
                'opening_cash' => (float) ($data['opening_cash'] ?? 0),
                'opening_note' => $data['opening_note'] ?? null,
                'opened_at' => now(),
            ]);
            $this->audit->record('shift.opened', 'shift', $shift->id, [], ['shift_no' => $shift->shift_no]);

            return $shift;
        }, 3);
    }

    public function close(Shift $shift, array $data): Shift
    {
        $businessId = $this->scope->businessId();
        $this->scope->assertBusinessRecord($shift, $businessId);

        return DB::transaction(function () use ($shift, $data, $businessId) {
            $locked = Shift::withoutGlobalScopes()
                ->where('business_id', $businessId)
                ->whereKey($shift->id)
                ->lockForUpdate()
                ->firstOrFail();
            $this->scope->assertLocationAccess((int) $locked->location_id);

            if ($locked->status !== 'open') {
                throw ValidationException::withMessages(['shift' => 'This shift is already closed.']);
            }

            $expected = (float) $locked->opening_cash
                + (float) $locked->payments()->where('payment_method', 'cash')->where('status', 'completed')->sum('amount')
                - (float) $locked->payments()->where('payment_method', 'cash')->where('status', 'refunded')->sum('amount');
            $closing = (float) $data['closing_cash'];

            $locked->update([
                'status' => 'closed',
                'expected_cash' => $expected,
                'closing_cash' => $closing,
                'cash_variance' => $closing - $expected,
                'closing_note' => $data['closing_note'] ?? null,
                'closed_at' => now(),
                'closed_by' => auth()->id(),
            ]);
            $this->audit->record('shift.closed', 'shift', $locked->id, [], [
                'expected_cash' => $expected,
                'closing_cash' => $closing,
                'variance' => $closing - $expected,
            ]);

            return $locked->fresh();
        }, 3);
    }
}
