<?php

namespace Modules\RestaurantNew\Services;

use Illuminate\Support\Facades\DB;
use Modules\RestaurantNew\Entities\RestaurantNewSaleOrder;
use Modules\RestaurantNew\Entities\RestaurantNewSaleOrderLine;
use Modules\RestaurantNew\Entities\RestaurantNewKitchenQueue;

class RestaurantSaleService
{
    public function createOrder(array $payload): RestaurantNewSaleOrder
    {
        return DB::transaction(function () use ($payload) {
            $items = $payload['items'] ?? [];
            $subTotal = collect($items)->sum(function ($item) {
                return (float)($item['quantity'] ?? 0) * (float)($item['unit_price'] ?? 0);
            });
            $discount = (float)($payload['discount_amount'] ?? 0);
            $tax = (float)($payload['tax_amount'] ?? 0);
            $serviceCharge = (float)($payload['service_charge_amount'] ?? 0);
            $grandTotal = max(0, $subTotal - $discount + $tax + $serviceCharge);

            $order = RestaurantNewSaleOrder::create([
                'business_id' => $payload['business_id'],
                'location_id' => $payload['location_id'] ?? null,
                'order_no' => $payload['order_no'] ?? $this->nextOrderNo(),
                'order_type' => $payload['order_type'] ?? 'dine_in',
                'table_id' => $payload['table_id'] ?? null,
                'customer_id' => $payload['customer_id'] ?? null,
                'waiter_id' => $payload['waiter_id'] ?? null,
                'cashier_id' => $payload['cashier_id'] ?? null,
                'status' => 'received',
                'kitchen_status' => 'received',
                'payment_status' => 'pending',
                'sub_total' => $subTotal,
                'discount_amount' => $discount,
                'tax_amount' => $tax,
                'service_charge_amount' => $serviceCharge,
                'grand_total' => $grandTotal,
                'created_by' => $payload['created_by'] ?? auth()->id(),
                'note' => $payload['note'] ?? null,
            ]);

            foreach ($items as $item) {
                $qty = (float)($item['quantity'] ?? 0);
                $price = (float)($item['unit_price'] ?? 0);
                RestaurantNewSaleOrderLine::create([
                    'order_id' => $order->id,
                    'menu_item_id' => $item['menu_item_id'] ?? null,
                    'menu_item_name' => $item['menu_item_name'] ?? 'Item',
                    'kitchen_section_id' => $item['kitchen_section_id'] ?? null,
                    'quantity' => $qty,
                    'unit_price' => $price,
                    'line_total' => $qty * $price,
                    'status' => 'received',
                    'note' => $item['note'] ?? null,
                ]);
            }

            $this->sendToKitchen($order);
            return $order->fresh('lines');
        });
    }

    public function sendToKitchen(RestaurantNewSaleOrder $order): RestaurantNewKitchenQueue
    {
        return RestaurantNewKitchenQueue::firstOrCreate(
            ['order_id' => $order->id],
            [
                'business_id' => $order->business_id,
                'location_id' => $order->location_id,
                'queue_no' => $order->order_no,
                'status' => 'received',
                'received_at' => now(),
                'print_count' => 0,
            ]
        );
    }

    public function updateKitchenStatus(RestaurantNewKitchenQueue $queue, string $status): RestaurantNewKitchenQueue
    {
        $data = ['status' => $status];
        if ($status === 'preparing') { $data['started_at'] = now(); }
        if ($status === 'ready') { $data['ready_at'] = now(); }
        if ($status === 'served') { $data['served_at'] = now(); }
        $queue->update($data);
        $queue->order()->update(['kitchen_status' => $status, 'status' => $status === 'served' ? 'served' : 'received']);
        return $queue->fresh('order.lines');
    }

    public function markPrinted(RestaurantNewKitchenQueue $queue): void
    {
        $queue->increment('print_count');
        $queue->update(['last_printed_at' => now()]);
    }

    protected function nextOrderNo(): string
    {
        return 'RN-' . now()->format('Ymd-His') . '-' . random_int(100, 999);
    }
}
