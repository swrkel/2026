<?php

namespace Modules\RestaurantNew\Services;

use Illuminate\Support\Facades\DB;
use Modules\RestaurantNew\Entities\RestaurantNewOrder;
use Modules\RestaurantNew\Entities\RestaurantNewOrderLine;
use Modules\RestaurantNew\Entities\RestaurantNewOrderPayment;

class RestaurantPosService
{
    public function createOrder(array $data): RestaurantNewOrder
    {
        return DB::transaction(function () use ($data) {
            $order = RestaurantNewOrder::create($this->orderPayload($data));

            foreach (($data['items'] ?? []) as $item) {
                $this->addLine($order, $item);
            }

            $this->recalculate($order);
            return $order->fresh(['lines', 'payments']);
        });
    }

    public function addLine(RestaurantNewOrder $order, array $item): RestaurantNewOrderLine
    {
        $qty = (float) ($item['qty'] ?? 1);
        $unitPrice = (float) ($item['unit_price'] ?? 0);
        $discount = (float) ($item['discount_amount'] ?? 0);
        $tax = (float) ($item['tax_amount'] ?? 0);

        return $order->lines()->create([
            'business_id' => $order->business_id,
            'location_id' => $order->location_id,
            'menu_item_id' => $item['menu_item_id'] ?? null,
            'variant_id' => $item['variant_id'] ?? null,
            'kitchen_section_id' => $item['kitchen_section_id'] ?? null,
            'item_name' => $item['item_name'] ?? '',
            'variant_name' => $item['variant_name'] ?? null,
            'qty' => $qty,
            'unit_price' => $unitPrice,
            'discount_amount' => $discount,
            'tax_amount' => $tax,
            'line_total' => (($qty * $unitPrice) - $discount + $tax),
            'kot_status' => 'pending',
            'line_note' => $item['line_note'] ?? null,
            'created_by' => auth()->id(),
        ]);
    }

    public function addPayment(RestaurantNewOrder $order, array $payment): RestaurantNewOrderPayment
    {
        $row = $order->payments()->create([
            'business_id' => $order->business_id,
            'location_id' => $order->location_id,
            'payment_method' => $payment['payment_method'] ?? 'cash',
            'payment_account_id' => $payment['payment_account_id'] ?? null,
            'amount' => $payment['amount'] ?? 0,
            'reference_no' => $payment['reference_no'] ?? null,
            'paid_on' => $payment['paid_on'] ?? now(),
            'payment_note' => $payment['payment_note'] ?? null,
            'created_by' => auth()->id(),
        ]);

        $this->recalculate($order);
        return $row;
    }

    public function recalculate(RestaurantNewOrder $order): RestaurantNewOrder
    {
        $subtotal = (float) $order->lines()->sum('line_total');
        $paid = (float) $order->payments()->sum('amount');
        $grand = $subtotal + (float) $order->service_charge_amount + (float) $order->delivery_charge + (float) $order->tax_amount - (float) $order->discount_amount + (float) $order->round_off;

        $order->fill([
            'subtotal' => $subtotal,
            'grand_total' => $grand,
            'paid_amount' => $paid,
            'balance_amount' => $grand - $paid,
            'payment_status' => $paid <= 0 ? 'due' : ($paid >= $grand ? 'paid' : 'partial'),
        ])->save();

        return $order;
    }

    protected function orderPayload(array $data): array
    {
        return [
            'business_id' => $data['business_id'],
            'location_id' => $data['location_id'] ?? null,
            'dining_area_id' => $data['dining_area_id'] ?? null,
            'restaurant_table_id' => $data['restaurant_table_id'] ?? null,
            'customer_id' => $data['customer_id'] ?? null,
            'order_no' => $data['order_no'] ?? $this->nextOrderNo($data['business_id']),
            'order_type' => $data['order_type'] ?? 'dine_in',
            'order_status' => 'open',
            'kot_status' => 'pending',
            'payment_status' => 'due',
            'guest_count' => $data['guest_count'] ?? 1,
            'waiter_id' => $data['waiter_id'] ?? auth()->id(),
            'cashier_id' => $data['cashier_id'] ?? null,
            'order_note' => $data['order_note'] ?? null,
            'opened_at' => now(),
            'created_by' => auth()->id(),
        ];
    }

    public function nextOrderNo(int $businessId): string
    {
        $next = (int) RestaurantNewOrder::where('business_id', $businessId)->max('id') + 1;
        return 'RNO-' . str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }
}
