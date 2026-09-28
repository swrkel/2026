<?php

namespace Modules\RestaurantNew\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\RestaurantNew\Entities\DiscountRule;
use Modules\RestaurantNew\Entities\DiscountUsage;
use Modules\RestaurantNew\Entities\ManagerApproval;
use Modules\RestaurantNew\Entities\Order;
use Modules\RestaurantNew\Entities\OrderAdjustment;

class DiscountService
{
    public function __construct(
        private TenantScopeService $scope,
        private AuditService $audit
    ) {
    }

    public function rule(array $data): DiscountRule
    {
        $businessId = $this->scope->businessId();
        abort_unless($businessId, 403);

        $locationId = (int) ($data['location_id'] ?? 0) ?: null;
        $this->scope->assertLocationAccess($locationId);

        $days = array_values(array_unique(array_map(
            'intval',
            array_filter((array) ($data['days'] ?? []), fn ($day) => in_array((int) $day, range(0, 6), true))
        )));

        $values = Arr::except($data, ['days']);
        $values['business_id'] = $businessId;
        $values['location_id'] = $locationId;
        $values['days_json'] = $days ?: null;
        $values['requires_manager'] = (bool) ($data['requires_manager'] ?? false);
        $values['is_active'] = array_key_exists('is_active', $data) ? (bool) $data['is_active'] : true;

        return DiscountRule::withoutGlobalScopes()->updateOrCreate(
            [
                'business_id' => $businessId,
                'rule_code' => $data['rule_code'],
            ],
            $values
        );
    }

    public function apply(Order $order, DiscountRule $rule, ?string $reason = null): Order
    {
        $businessId = $this->scope->businessId();
        $this->scope->assertBusinessRecord($order, $businessId);
        $this->scope->assertBusinessRecord($rule, $businessId);

        if ($order->payment_status === 'paid' || in_array($order->status, ['cancelled', 'completed'], true)) {
            throw ValidationException::withMessages(['order' => 'Discounts can only be applied to an open unpaid order.']);
        }
        if ($order->discount_rule_id || $order->discountUsages()->exists()) {
            throw ValidationException::withMessages(['discount_rule_id' => 'A controlled discount has already been applied to this order.']);
        }

        $now = now();
        if (! $rule->is_active
            || ($rule->starts_on && $now->lt($rule->starts_on->copy()->startOfDay()))
            || ($rule->ends_on && $now->gt($rule->ends_on->copy()->endOfDay()))) {
            throw ValidationException::withMessages(['discount_rule_id' => 'This discount rule is not currently active.']);
        }
        if ($rule->location_id && (int) $rule->location_id !== (int) $order->location_id) {
            throw ValidationException::withMessages(['discount_rule_id' => 'The discount rule belongs to another location.']);
        }
        if ($rule->order_type && $rule->order_type !== $order->order_type) {
            throw ValidationException::withMessages(['discount_rule_id' => 'The discount is not valid for this order type.']);
        }
        if ((float) $order->subtotal < (float) $rule->minimum_order) {
            throw ValidationException::withMessages(['discount_rule_id' => 'The minimum order amount has not been reached.']);
        }
        if ($rule->days_json !== null && ! in_array($now->dayOfWeek, array_map('intval', (array) $rule->days_json), true)) {
            throw ValidationException::withMessages(['discount_rule_id' => 'The discount is not available today.']);
        }
        if ($rule->starts_at && $rule->ends_at) {
            $time = $now->format('H:i:s');
            if ($time < $rule->starts_at || $time > $rule->ends_at) {
                throw ValidationException::withMessages(['discount_rule_id' => 'The discount is not available at this time.']);
            }
        }
        if ($rule->requires_manager && ! auth()->user()?->can('restaurant_new.discounts.approve')) {
            abort(403, 'Manager approval permission is required for this discount.');
        }

        return DB::transaction(function () use ($order, $rule, $reason, $businessId) {
            $locked = Order::withoutGlobalScopes()
                ->where('business_id', $businessId)
                ->whereKey($order->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->payment_status === 'paid' || in_array($locked->status, ['cancelled', 'completed'], true)) {
                throw ValidationException::withMessages([
                    'order' => 'Discounts can only be applied to an open unpaid order.',
                ]);
            }
            if ($locked->discount_rule_id || DiscountUsage::withoutGlobalScopes()->where('order_id', $locked->id)->exists()) {
                throw ValidationException::withMessages([
                    'discount_rule_id' => 'A controlled discount has already been applied to this order.',
                ]);
            }

            $existingLineDiscount = (float) $locked->items()
                ->whereNotIn('status', ['voided', 'cancelled'])
                ->sum('discount_amount');

            $discount = $rule->discount_type === 'fixed'
                ? (float) $rule->discount_value
                : ((float) $locked->subtotal * ((float) $rule->discount_value / 100));

            if ($rule->maximum_discount !== null) {
                $discount = min($discount, (float) $rule->maximum_discount);
            }
            $discount = max(0, min((float) $locked->subtotal - $existingLineDiscount, $discount));

            $before = $locked->only(['discount_total', 'total_amount', 'balance_amount']);
            $newDiscount = $existingLineDiscount + $discount;
            $newTotal = max(
                0,
                (float) $locked->subtotal
                - $newDiscount
                + (float) $locked->tax_total
                + (float) $locked->service_charge_total
                + (float) ($locked->delivery_fee ?? 0)
                + (float) $locked->rounding_amount
            );

            $locked->update([
                'discount_rule_id' => $rule->id,
                'discount_total' => $newDiscount,
                'discount_reason' => $reason ?: $rule->name,
                'discount_authorized_by' => auth()->id(),
                'discount_authorized_at' => now(),
                'total_amount' => $newTotal,
                'balance_amount' => max(0, $newTotal - (float) $locked->paid_amount),
            ]);

            DiscountUsage::withoutGlobalScopes()->create([
                'business_id' => $businessId,
                'order_id' => $locked->id,
                'discount_rule_id' => $rule->id,
                'rule_code' => $rule->rule_code,
                'rule_name' => $rule->name,
                'discount_amount' => $discount,
                'reason' => $reason,
                'applied_by' => auth()->id(),
                'approved_by' => $rule->requires_manager ? auth()->id() : null,
            ]);

            OrderAdjustment::withoutGlobalScopes()->create([
                'business_id' => $businessId,
                'order_id' => $locked->id,
                'adjustment_type' => 'discount',
                'amount' => -$discount,
                'reason' => $reason ?: $rule->name,
                'before_json' => $before,
                'after_json' => $locked->fresh()->only(['discount_total', 'total_amount', 'balance_amount']),
                'requested_by' => auth()->id(),
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);

            if ($rule->requires_manager) {
                ManagerApproval::withoutGlobalScopes()->create([
                    'business_id' => $businessId,
                    'location_id' => $locked->location_id,
                    'approval_type' => 'discount',
                    'entity_type' => 'order',
                    'entity_id' => $locked->id,
                    'status' => 'approved',
                    'reason' => $reason ?: $rule->name,
                    'payload_json' => ['rule_id' => $rule->id, 'discount_amount' => $discount],
                    'requested_by' => auth()->id(),
                    'approved_by' => auth()->id(),
                    'approved_at' => now(),
                ]);
            }

            $this->audit->record(
                'order.discount_applied',
                'order',
                $locked->id,
                $before,
                ['rule' => $rule->rule_code, 'discount' => $discount]
            );

            return $locked->fresh();
        }, 3);
    }
}
