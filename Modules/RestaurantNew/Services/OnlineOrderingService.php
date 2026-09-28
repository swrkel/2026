<?php

namespace Modules\RestaurantNew\Services;

use Illuminate\Support\Facades\DB;
use Modules\RestaurantNew\Entities\RestaurantNewOnlineCustomer;
use Modules\RestaurantNew\Entities\RestaurantNewOnlineOrder;
use Modules\RestaurantNew\Entities\RestaurantNewOnlineOrderLine;
use Modules\RestaurantNew\Entities\RestaurantNewOnlineOrderStatusLog;

class OnlineOrderingService
{
    public function createOrder(array $data, array $lines): RestaurantNewOnlineOrder
    {
        return DB::transaction(function () use ($data, $lines) {
            $customer = RestaurantNewOnlineCustomer::firstOrCreate(
                ['business_id' => $data['business_id'], 'mobile' => $data['mobile']],
                [
                    'customer_name' => $data['customer_name'] ?? 'Online Customer',
                    'email' => $data['email'] ?? null,
                    'default_address' => $data['delivery_address'] ?? null,
                    'city' => $data['city'] ?? null,
                    'landmark' => $data['landmark'] ?? null,
                ]
            );

            $totals = $this->calculateTotals($lines, (float)($data['delivery_charge'] ?? 0));
            $order = RestaurantNewOnlineOrder::create(array_merge($data, $totals, [
                'online_customer_id' => $customer->id,
                'online_order_no' => $data['online_order_no'] ?? $this->nextOrderNo((int)$data['business_id']),
                'status' => 'received',
                'payment_status' => $data['payment_status'] ?? 'pending',
            ]));

            foreach ($lines as $line) {
                RestaurantNewOnlineOrderLine::create(array_merge($line, [
                    'business_id' => $data['business_id'],
                    'online_order_id' => $order->id,
                    'line_total' => $this->lineTotal($line),
                ]));
            }

            $this->logStatus($order, null, 'received', 'Online order received');
            return $order->fresh(['lines', 'customer']);
        });
    }

    public function changeStatus(RestaurantNewOnlineOrder $order, string $status, ?string $remarks = null, ?int $userId = null): RestaurantNewOnlineOrder
    {
        return DB::transaction(function () use ($order, $status, $remarks, $userId) {
            $from = $order->status;
            $order->status = $status;
            if ($status === 'accepted') { $order->accepted_at = now(); }
            if ($status === 'ready') { $order->ready_at = now(); }
            if (in_array($status, ['completed','delivered','cancelled'], true)) { $order->completed_at = now(); }
            $order->save();
            $this->logStatus($order, $from, $status, $remarks, $userId);
            return $order->fresh(['lines', 'customer']);
        });
    }

    public function buildKitchenQueue(int $businessId, ?int $locationId = null)
    {
        return RestaurantNewOnlineOrder::with(['lines', 'customer'])
            ->where('business_id', $businessId)
            ->when($locationId, fn($q) => $q->where('location_id', $locationId))
            ->whereIn('status', ['received','accepted','preparing'])
            ->orderByRaw("FIELD(status, 'received', 'accepted', 'preparing')")
            ->orderBy('scheduled_at')
            ->orderBy('created_at')
            ->get();
    }

    protected function calculateTotals(array $lines, float $deliveryCharge = 0): array
    {
        $subtotal = 0; $discount = 0; $tax = 0;
        foreach ($lines as $line) {
            $subtotal += ((float)($line['quantity'] ?? 0)) * ((float)($line['unit_price'] ?? 0));
            $discount += (float)($line['discount_amount'] ?? 0);
            $tax += (float)($line['tax_amount'] ?? 0);
        }
        return [
            'subtotal' => $subtotal,
            'discount_amount' => $discount,
            'tax_amount' => $tax,
            'delivery_charge' => $deliveryCharge,
            'total_amount' => $subtotal - $discount + $tax + $deliveryCharge,
        ];
    }

    protected function lineTotal(array $line): float
    {
        return (((float)($line['quantity'] ?? 0)) * ((float)($line['unit_price'] ?? 0))) - (float)($line['discount_amount'] ?? 0) + (float)($line['tax_amount'] ?? 0);
    }

    protected function nextOrderNo(int $businessId): string
    {
        return 'ONL-' . $businessId . '-' . now()->format('YmdHis');
    }

    protected function logStatus(RestaurantNewOnlineOrder $order, ?string $from, string $to, ?string $remarks = null, ?int $userId = null): void
    {
        RestaurantNewOnlineOrderStatusLog::create([
            'business_id' => $order->business_id,
            'online_order_id' => $order->id,
            'from_status' => $from,
            'to_status' => $to,
            'remarks' => $remarks,
            'changed_by' => $userId,
        ]);
    }
}
