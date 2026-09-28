<?php

namespace Modules\RestaurantNew\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\RestaurantNew\Entities\DeliveryDispatch;
use Modules\RestaurantNew\Entities\DeliveryZone;
use Modules\RestaurantNew\Entities\Order;

class DeliveryService
{
    public function __construct(
        private TenantScopeService $scope,
        private NumberService $numbers,
        private AuditService $audit
    ) {
    }

    public function zone(array $data): DeliveryZone
    {
        $businessId = $this->scope->businessId();
        abort_unless($businessId, 403);
        $locationId = (int) ($data['location_id'] ?? 0) ?: null;
        $this->scope->assertLocationAccess($locationId);

        $values = Arr::only($data, [
            'zone_code', 'name', 'minimum_order', 'delivery_fee', 'estimated_minutes', 'is_active',
        ]);
        $values['business_id'] = $businessId;
        $values['location_id'] = $locationId;
        $values['is_active'] = array_key_exists('is_active', $data) ? (bool) $data['is_active'] : true;

        return DeliveryZone::withoutGlobalScopes()->updateOrCreate(
            [
                'business_id' => $businessId,
                'location_id' => $locationId,
                'zone_code' => $data['zone_code'],
            ],
            $values
        );
    }

    public function createForOrder(Order $order, array $data = []): DeliveryDispatch
    {
        $businessId = $this->scope->businessId();
        $this->scope->assertBusinessRecord($order, $businessId);

        if ($order->order_type !== 'delivery') {
            throw ValidationException::withMessages(['order' => 'Only delivery orders can be dispatched.']);
        }
        if (in_array($order->status, ['cancelled', 'completed'], true)) {
            throw ValidationException::withMessages(['order' => 'This order cannot be dispatched.']);
        }

        return DB::transaction(function () use ($order, $data, $businessId) {
            $locked = Order::withoutGlobalScopes()
                ->where('business_id', $businessId)
                ->whereKey($order->id)
                ->lockForUpdate()
                ->firstOrFail();

            $zoneId = (int) ($data['delivery_zone_id'] ?? $locked->delivery_zone_id ?? 0) ?: null;
            if ($zoneId) {
                $zone = DeliveryZone::withoutGlobalScopes()
                    ->where('business_id', $businessId)
                    ->whereKey($zoneId)
                    ->where('is_active', true)
                    ->first();
                if (! $zone || ($zone->location_id && (int) $zone->location_id !== (int) $locked->location_id)) {
                    throw ValidationException::withMessages(['delivery_zone_id' => 'Invalid delivery zone for this location.']);
                }
            }

            $address = trim((string) ($data['delivery_address'] ?? $locked->delivery_address ?? ''));
            if ($address === '') {
                throw ValidationException::withMessages(['delivery_address' => 'Delivery address is required.']);
            }

            return DeliveryDispatch::withoutGlobalScopes()->firstOrCreate(
                ['order_id' => $locked->id],
                [
                    'business_id' => $businessId,
                    'location_id' => $locked->location_id,
                    'delivery_zone_id' => $zoneId,
                    'dispatch_no' => $this->numbers->next($businessId, 'dispatch', 'DSP-'),
                    'status' => 'waiting',
                    'delivery_address' => $address,
                    'customer_phone' => $locked->customer_phone,
                    'instructions' => $data['instructions'] ?? $locked->notes,
                    'cash_to_collect' => max(0, (float) $locked->balance_amount),
                    'created_by' => auth()->id(),
                ]
            );
        }, 3);
    }

    public function assign(DeliveryDispatch $dispatch, ?int $driverUserId): DeliveryDispatch
    {
        $businessId = $this->scope->businessId();
        $this->scope->assertBusinessRecord($dispatch, $businessId);

        if ($driverUserId && DB::getSchemaBuilder()->hasTable('users')) {
            $validDriver = DB::table('users')
                ->where('id', $driverUserId)
                ->where('business_id', $businessId)
                ->exists();
            if (! $validDriver) {
                throw ValidationException::withMessages([
                    'driver_user_id' => 'The selected driver does not belong to this business.',
                ]);
            }
        }

        return DB::transaction(function () use ($dispatch, $driverUserId, $businessId) {
            $locked = DeliveryDispatch::withoutGlobalScopes()
                ->where('business_id', $businessId)
                ->whereKey($dispatch->id)
                ->lockForUpdate()
                ->firstOrFail();
            $this->scope->assertLocationAccess((int) $locked->location_id);

            if (in_array($locked->status, ['dispatched', 'delivered', 'cancelled'], true)) {
                throw ValidationException::withMessages([
                    'dispatch' => 'This delivery can no longer be reassigned.',
                ]);
            }

            $locked->update([
                'driver_user_id' => $driverUserId,
                'status' => $driverUserId ? 'assigned' : 'waiting',
                'assigned_at' => $driverUserId ? now() : null,
            ]);

            $this->audit->record(
                'delivery.assigned',
                'delivery_dispatch',
                $locked->id,
                [],
                ['driver_user_id' => $driverUserId]
            );

            return $locked->fresh();
        }, 3);
    }

    public function status(DeliveryDispatch $dispatch, string $status, float $cashCollected = 0): DeliveryDispatch
    {
        $businessId = $this->scope->businessId();
        $this->scope->assertBusinessRecord($dispatch, $businessId);

        return DB::transaction(function () use ($dispatch, $status, $cashCollected, $businessId) {
            $locked = DeliveryDispatch::withoutGlobalScopes()
                ->where('business_id', $businessId)
                ->whereKey($dispatch->id)
                ->lockForUpdate()
                ->firstOrFail();
            $this->scope->assertLocationAccess((int) $locked->location_id);

            $allowed = [
                'waiting' => ['assigned', 'cancelled'],
                'assigned' => ['waiting', 'dispatched', 'cancelled'],
                'dispatched' => ['delivered', 'failed'],
                'failed' => ['assigned', 'cancelled'],
                'delivered' => [],
                'cancelled' => [],
            ];
            if ($status !== $locked->status && ! in_array($status, $allowed[$locked->status] ?? [], true)) {
                throw ValidationException::withMessages([
                    'status' => 'Invalid delivery status transition.',
                ]);
            }
            if ($status === 'dispatched' && ! $locked->driver_user_id) {
                throw ValidationException::withMessages([
                    'driver_user_id' => 'Assign a driver before dispatching the order.',
                ]);
            }
            if ($status === $locked->status) {
                return $locked->fresh(['order', 'zone']);
            }

            $changes = ['status' => $status];
            if ($status === 'dispatched' && ! $locked->dispatched_at) {
                $changes['dispatched_at'] = now();
            }
            if ($status === 'delivered') {
                $changes['delivered_at'] = now();
                $changes['cash_collected'] = max(0, $cashCollected);
            }
            $locked->update($changes);

            $order = Order::withoutGlobalScopes()
                ->where('business_id', $businessId)
                ->whereKey($locked->order_id)
                ->lockForUpdate()
                ->first();
            if ($order) {
                if ($status === 'dispatched' && ! in_array($order->status, ['cancelled', 'completed'], true)) {
                    $order->update(['status' => 'out_for_delivery']);
                }
                if ($status === 'delivered') {
                    $order->update([
                        'status' => $order->payment_status === 'paid' ? 'completed' : 'delivered',
                        'completed_at' => $order->payment_status === 'paid' ? now() : $order->completed_at,
                    ]);
                }
            }

            $this->audit->record(
                'delivery.status_changed',
                'delivery_dispatch',
                $locked->id,
                [],
                ['status' => $status, 'cash_collected' => max(0, $cashCollected)]
            );

            return $locked->fresh(['order', 'zone']);
        }, 3);
    }

}
