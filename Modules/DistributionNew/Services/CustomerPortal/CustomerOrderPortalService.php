<?php

namespace Modules\DistributionNew\Services\CustomerPortal;

use Illuminate\Support\Facades\DB;

class CustomerOrderPortalService
{
    public function createOrder(array $payload, int $businessId, int $customerId, ?int $createdBy): int
    {
        return DB::transaction(function () use ($payload, $businessId, $customerId, $createdBy) {
            $orderId = DB::table('disnew_sales_orders')->insertGetId([
                'business_id' => $businessId,
                'location_id' => $payload['location_id'] ?? null,
                'customer_id' => $customerId,
                'order_no' => $payload['order_no'] ?? ('DNC-' . now()->format('YmdHis')),
                'order_date' => $payload['order_date'] ?? now()->toDateString(),
                'status' => 'pending_approval',
                'source' => 'customer_portal',
                'created_by' => $createdBy,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach (($payload['lines'] ?? []) as $line) {
                DB::table('disnew_sales_order_lines')->insert([
                    'sales_order_id' => $orderId,
                    'product_id' => $line['product_id'],
                    'quantity' => $line['quantity'],
                    'unit_price' => $line['unit_price'] ?? 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            return $orderId;
        });
    }
}
