<?php

namespace Modules\RestaurantNew\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\RestaurantNew\Entities\DeliveryZone;
use Modules\RestaurantNew\Entities\DiningTable;
use Modules\RestaurantNew\Entities\MenuItem;
use Modules\RestaurantNew\Entities\Order;
use Modules\RestaurantNew\Entities\OrderItem;
use Modules\RestaurantNew\Entities\OrderItemModifier;
use Modules\RestaurantNew\Entities\Reservation;

class OrderService
{
    public function __construct(
        private TenantScopeService $scope,
        private ShiftService $shifts,
        private NumberService $numbers,
        private OrderPricingService $pricing,
        private KitchenService $kitchen,
        private CollectionService $collection,
        private AuditService $audit,
        private SettingService $settings
    ) {
    }

    public function create(array $data): Order
    {
        $businessId = $this->scope->businessId();
        abort_unless($businessId, 403);

        $orderType = $data['order_type'] ?? 'dine_in';
        $tableId = $orderType === 'dine_in' ? ((int) ($data['table_id'] ?? 0) ?: null) : null;
        $locationId = (int) ($data['location_id'] ?? 0) ?: $this->scope->currentLocationId();

        if ($tableId) {
            $table = DiningTable::withoutGlobalScopes()
                ->where('business_id', $businessId)
                ->whereKey($tableId)
                ->where('is_active', true)
                ->first();

            if (! $table) {
                throw ValidationException::withMessages(['table_id' => 'The selected dining table is unavailable.']);
            }
            if ($table->location_id && $locationId && (int) $table->location_id !== (int) $locationId) {
                throw ValidationException::withMessages(['table_id' => 'The selected dining table belongs to another location.']);
            }
            $locationId ??= (int) $table->location_id ?: null;
        }

        $this->scope->assertLocationAccess($locationId);
        $shift = $this->shifts->current($locationId);
        if (! $locationId && $shift?->location_id) {
            $locationId = (int) $shift->location_id;
            $this->scope->assertLocationAccess($locationId);
        }

        if ($orderType !== 'dine_in'
            && (string) $this->settings->get('require_customer_for_takeaway', '0') === '1'
            && empty(trim((string) ($data['customer_name'] ?? '')))
            && empty(trim((string) ($data['customer_phone'] ?? '')))) {
            throw ValidationException::withMessages([
                'customer_name' => 'Customer name or phone is required for takeaway and delivery orders.',
            ]);
        }

        $reservationId = (int) ($data['reservation_id'] ?? 0) ?: null;
        if ($reservationId) {
            $reservation = Reservation::withoutGlobalScopes()
                ->where('business_id', $businessId)
                ->whereKey($reservationId)
                ->whereIn('status', ['booked', 'confirmed'])
                ->first();

            if (! $reservation || ($reservation->location_id && (int) $reservation->location_id !== (int) $locationId)) {
                throw ValidationException::withMessages(['reservation_id' => 'The selected reservation is unavailable.']);
            }
            if ($tableId && $reservation->table_id && (int) $reservation->table_id !== $tableId) {
                throw ValidationException::withMessages(['table_id' => 'The table does not match the selected reservation.']);
            }
        }

        $deliveryZone = null;
        if ($orderType === 'delivery') {
            if (empty(trim((string) ($data['delivery_address'] ?? '')))) {
                throw ValidationException::withMessages(['delivery_address' => 'Delivery address is required.']);
            }

            $zoneId = (int) ($data['delivery_zone_id'] ?? 0) ?: null;
            if ($zoneId) {
                $deliveryZone = DeliveryZone::withoutGlobalScopes()
                    ->where('business_id', $businessId)
                    ->whereKey($zoneId)
                    ->where('is_active', true)
                    ->first();

                if (! $deliveryZone || ($deliveryZone->location_id && (int) $deliveryZone->location_id !== (int) $locationId)) {
                    throw ValidationException::withMessages(['delivery_zone_id' => 'The selected delivery zone is unavailable.']);
                }
            }
        }

        if (config('restaurantnew.require_open_shift_for_orders', true) && ! $shift) {
            throw ValidationException::withMessages(['shift' => 'Open a restaurant shift before creating an order.']);
        }

        return DB::transaction(function () use (
            $data,
            $businessId,
            $locationId,
            $shift,
            $orderType,
            $tableId,
            $reservationId,
            $deliveryZone
        ) {
            if ($tableId) {
                $lockedTable = DiningTable::withoutGlobalScopes()
                    ->where('business_id', $businessId)
                    ->whereKey($tableId)
                    ->where('is_active', true)
                    ->lockForUpdate()
                    ->first();

                if (! $lockedTable || $lockedTable->status !== 'available') {
                    throw ValidationException::withMessages(['table_id' => 'The selected dining table is already occupied or unavailable.']);
                }
                if ($lockedTable->location_id && $locationId && (int) $lockedTable->location_id !== (int) $locationId) {
                    throw ValidationException::withMessages(['table_id' => 'The selected dining table belongs to another location.']);
                }
            }

            $lines = [];
            $resolved = [];

            foreach ($data['items'] as $rowIndex => $row) {
                $item = MenuItem::withoutGlobalScopes()
                    ->with([
                        'modifierGroups' => fn ($q) => $q->where('restnew_modifier_groups.is_active', true),
                        'modifierGroups.modifiers' => fn ($q) => $q->where('is_active', true),
                    ])
                    ->where('business_id', $businessId)
                    ->whereKey((int) $row['menu_item_id'])
                    ->where('is_active', true)
                    ->where('is_available', true)
                    ->first();

                if (! $item) {
                    throw ValidationException::withMessages(["items.$rowIndex.menu_item_id" => 'A selected menu item is unavailable.']);
                }
                if ($item->location_id && ! $locationId) {
                    throw ValidationException::withMessages(["items.$rowIndex.menu_item_id" => 'Select a location before ordering this menu item.']);
                }
                if ($item->location_id && (int) $item->location_id !== (int) $locationId) {
                    throw ValidationException::withMessages(["items.$rowIndex.menu_item_id" => 'A selected menu item belongs to another location.']);
                }
                if ($orderType === 'dine_in' && ! $item->is_dine_in) {
                    throw ValidationException::withMessages(["items.$rowIndex.menu_item_id" => $item->name.' is not available for dine-in.']);
                }
                if ($orderType === 'takeaway' && ! $item->is_takeaway) {
                    throw ValidationException::withMessages(["items.$rowIndex.menu_item_id" => $item->name.' is not available for takeaway.']);
                }
                if ($orderType === 'delivery' && ! $item->is_delivery) {
                    throw ValidationException::withMessages(["items.$rowIndex.menu_item_id" => $item->name.' is not available for delivery.']);
                }

                $qty = (float) $row['quantity'];
                if ($qty <= 0) {
                    throw ValidationException::withMessages(["items.$rowIndex.quantity" => 'Item quantity must be greater than zero.']);
                }

                $selectedIds = array_values(array_unique(array_map('intval', (array) ($row['modifier_ids'] ?? []))));
                $mods = [];
                $modifierUnit = 0.0;

                foreach ($item->modifierGroups as $group) {
                    $available = $group->modifiers->keyBy('id');
                    $selectedForGroup = array_values(array_filter($selectedIds, fn ($id) => $available->has($id)));
                    $minimum = max((int) $group->min_select, $group->is_required ? 1 : 0);
                    $maximum = max(1, (int) $group->max_select);

                    if (count($selectedForGroup) < $minimum || count($selectedForGroup) > $maximum) {
                        throw ValidationException::withMessages([
                            "items.$rowIndex.modifier_ids" => $item->name.': '.$group->name.' requires '.$minimum.' to '.$maximum.' selection(s).',
                        ]);
                    }

                    foreach ($selectedForGroup as $modifierId) {
                        $modifier = $available->get($modifierId);
                        $mods[] = $modifier;
                        $modifierUnit += (float) $modifier->price_delta;
                    }
                }

                $allowedIds = $item->modifierGroups
                    ->flatMap(fn ($group) => $group->modifiers->pluck('id'))
                    ->map(fn ($id) => (int) $id)
                    ->all();

                if (array_diff($selectedIds, $allowedIds)) {
                    throw ValidationException::withMessages([
                        "items.$rowIndex.modifier_ids" => 'An invalid modifier was submitted for '.$item->name.'.',
                    ]);
                }

                $basePrice = $orderType !== 'dine_in' && $item->takeaway_price !== null
                    ? (float) $item->takeaway_price
                    : (float) $item->selling_price;

                $lineDiscount = (float) ($row['discount_amount'] ?? 0);
                if ($lineDiscount > 0 && ! auth()->user()?->can('restaurant_new.discounts.apply')) {
                    throw ValidationException::withMessages([
                        "items.$rowIndex.discount_amount" => 'You do not have permission to apply item discounts.',
                    ]);
                }

                $line = $this->pricing->line(
                    $item,
                    $qty,
                    $modifierUnit,
                    $lineDiscount,
                    $basePrice
                );
                $lines[] = $line;
                $resolved[] = [$item, $qty, $mods, $line, $row['notes'] ?? null];
            }

            if (! $resolved) {
                throw ValidationException::withMessages(['items' => 'Add at least one menu item.']);
            }

            $totals = $this->pricing->totals($lines);
            if ($deliveryZone) {
                if ((float) $totals['total_amount'] < (float) $deliveryZone->minimum_order) {
                    throw ValidationException::withMessages([
                        'delivery_zone_id' => 'The delivery-zone minimum order has not been reached.',
                    ]);
                }
                $totals['delivery_fee'] = (float) $deliveryZone->delivery_fee;
                $totals['total_amount'] = round((float) $totals['total_amount'] + (float) $deliveryZone->delivery_fee, 4);
                $totals['balance_amount'] = $totals['total_amount'];
            }

            $order = Order::withoutGlobalScopes()->create(array_merge($totals, [
                'business_id' => $businessId,
                'location_id' => $locationId,
                'shift_id' => $shift?->id,
                'table_id' => $tableId,
                'waiter_id' => auth()->id(),
                'order_no' => $this->numbers->next($businessId, 'order', 'RO-'),
                'order_type' => $orderType,
                'source' => $data['source'] ?? 'waiter',
                'customer_name' => $data['customer_name'] ?? null,
                'customer_phone' => $data['customer_phone'] ?? null,
                'customer_email' => $data['customer_email'] ?? null,
                'reservation_id' => $reservationId,
                'delivery_zone_id' => $deliveryZone?->id,
                'delivery_address' => $data['delivery_address'] ?? null,
                'delivery_fee' => (float) ($totals['delivery_fee'] ?? 0),
                'guest_count' => (int) ($data['guest_count'] ?? 1),
                'notes' => $data['notes'] ?? null,
                'status' => 'draft',
                'payment_status' => 'unpaid',
                'kitchen_status' => 'not_sent',
                'opened_at' => now(),
            ]));

            foreach ($resolved as [$item, $qty, $mods, $line, $notes]) {
                $orderItem = OrderItem::withoutGlobalScopes()->create(array_merge($line, [
                    'business_id' => $businessId,
                    'order_id' => $order->id,
                    'menu_item_id' => $item->id,
                    'station_id' => $item->station_id,
                    'item_code' => $item->item_code,
                    'item_name' => $item->name,
                    'quantity' => $qty,
                    'notes' => $notes,
                    'status' => 'pending',
                ]));

                foreach ($mods as $modifier) {
                    OrderItemModifier::withoutGlobalScopes()->create([
                        'business_id' => $businessId,
                        'order_item_id' => $orderItem->id,
                        'modifier_id' => $modifier->id,
                        'modifier_name' => $modifier->name,
                        'quantity' => 1,
                        'price_delta' => $modifier->price_delta,
                        'line_total' => $modifier->price_delta * $qty,
                    ]);
                }
            }

            if ($order->table_id) {
                DiningTable::withoutGlobalScopes()
                    ->where('business_id', $businessId)
                    ->whereKey($order->table_id)
                    ->update(['status' => 'occupied']);
            }

            if ($reservationId) {
                Reservation::withoutGlobalScopes()
                    ->where('business_id', $businessId)
                    ->whereKey($reservationId)
                    ->update([
                        'status' => 'seated',
                        'seated_order_id' => $order->id,
                        'seated_at' => now(),
                    ]);
            }

            if ($order->order_type === 'takeaway') {
                $this->collection->issue($order);
            }

            $this->audit->record(
                'order.created',
                'order',
                $order->id,
                [],
                ['order_no' => $order->order_no, 'order_type' => $order->order_type]
            );

            if ($data['send_to_kitchen'] ?? true) {
                return $this->kitchen->send($order);
            }

            return $order->fresh(['items.modifiers', 'collectionToken']);
        }, 3);
    }

    public function cancel(Order $order, string $reason): Order
    {
        $businessId = $this->scope->businessId();
        $this->scope->assertBusinessRecord($order, $businessId);

        if ($order->payment_status === 'paid') {
            throw ValidationException::withMessages(['order' => 'A paid order cannot be cancelled. Use the refund process.']);
        }
        if (in_array($order->status, ['completed', 'cancelled'], true)) {
            throw ValidationException::withMessages(['order' => 'This order cannot be cancelled.']);
        }

        DB::transaction(function () use ($order, $reason, $businessId) {
            $order->items()->update(['status' => 'cancelled']);
            $order->tickets()->update(['status' => 'cancelled']);
            $order->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'notes' => trim(($order->notes ? $order->notes."\n" : '').'Cancellation: '.$reason),
            ]);

            if ($order->table_id) {
                DiningTable::withoutGlobalScopes()
                    ->where('business_id', $businessId)
                    ->whereKey($order->table_id)
                    ->update(['status' => 'available']);
            }
        });

        $this->audit->record('order.cancelled', 'order', $order->id, [], ['reason' => $reason]);

        return $order->fresh();
    }
}
