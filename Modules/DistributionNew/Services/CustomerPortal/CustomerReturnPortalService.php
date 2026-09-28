<?php

namespace Modules\DistributionNew\Services\CustomerPortal;

use Illuminate\Support\Facades\DB;

class CustomerReturnPortalService
{
    public function createReturnRequest(array $payload, int $businessId, int $customerId, ?int $createdBy): int
    {
        return DB::transaction(function () use ($payload, $businessId, $customerId, $createdBy) {
            $id = DB::table('disnew_customer_return_requests')->insertGetId([
                'business_id' => $businessId,
                'location_id' => $payload['location_id'] ?? null,
                'customer_id' => $customerId,
                'sales_order_id' => $payload['sales_order_id'] ?? null,
                'sales_invoice_id' => $payload['sales_invoice_id'] ?? null,
                'request_no' => $payload['request_no'] ?? ('DNRR-' . now()->format('YmdHis')),
                'request_date' => $payload['request_date'] ?? now()->toDateString(),
                'status' => 'submitted',
                'reason' => $payload['reason'] ?? null,
                'customer_note' => $payload['customer_note'] ?? null,
                'created_by' => $createdBy,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            foreach (($payload['lines'] ?? []) as $line) {
                DB::table('disnew_customer_return_request_lines')->insert([
                    'return_request_id' => $id,
                    'product_id' => $line['product_id'],
                    'qty' => $line['qty'],
                    'unit_price' => $line['unit_price'] ?? 0,
                    'line_note' => $line['line_note'] ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            return $id;
        });
    }
}
