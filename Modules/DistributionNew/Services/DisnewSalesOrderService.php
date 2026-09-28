<?php

namespace Modules\DistributionNew\Services;

use Illuminate\Support\Facades\DB;
use Modules\DistributionNew\Utils\DisnewNumberUtil;
use Modules\DistributionNew\Utils\DisnewTenantUtil;

class DisnewSalesOrderService
{
    public function create(array $data, string $source = 'user'): int
    {
        return DB::transaction(function () use ($data, $source) {
            $businessId = DisnewTenantUtil::businessId();
            $locationId = DisnewTenantUtil::locationId();
            $orderNo = DisnewNumberUtil::next('sales_order', 'DSO-', $businessId);
            $orderId = DB::table('disnew_sales_orders')->insertGetId([
                'business_id' => $businessId,
                'location_id' => $locationId,
                'sales_order_no' => $orderNo,
                'source' => $source,
                'customer_id' => $data['customer_id'] ?? null,
                'sales_rep_id' => $data['sales_rep_id'] ?? DisnewTenantUtil::userId(),
                'order_date' => $data['order_date'] ?? now()->toDateString(),
                'delivery_date' => $data['delivery_date'] ?? null,
                'status' => 'draft',
                'subtotal' => 0,
                'discount_total' => 0,
                'tax_total' => 0,
                'grand_total' => 0,
                'note' => $data['note'] ?? null,
                'created_by' => DisnewTenantUtil::userId(),
                'updated_by' => DisnewTenantUtil::userId(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $this->syncLines($orderId, $data['items'] ?? []);
            app(DisnewSmsService::class)->queue($businessId, $locationId, 'sales_order_created', $data['customer_id'] ?? null, $data['customer_mobile'] ?? null, 'Sales order '.$orderNo.' has been created.', ['sales_order_id' => $orderId]);
            return $orderId;
        });
    }

    public function syncLines(int $orderId, array $items): void
    {
        DB::table('disnew_sales_order_lines')->where('sales_order_id', $orderId)->delete();
        $subtotal = $discount = $tax = $grand = 0;
        foreach ($items as $item) {
            $qty = (float) ($item['qty'] ?? 0); $price = (float) ($item['unit_price'] ?? 0); $lineDiscount = (float) ($item['discount'] ?? 0); $lineTax = (float) ($item['tax'] ?? 0);
            $lineTotal = ($qty * $price) - $lineDiscount + $lineTax;
            $subtotal += $qty * $price; $discount += $lineDiscount; $tax += $lineTax; $grand += $lineTotal;
            DB::table('disnew_sales_order_lines')->insert(['sales_order_id' => $orderId, 'product_id' => $item['product_id'] ?? null, 'product_name' => $item['product_name'] ?? null, 'qty' => $qty, 'unit_price' => $price, 'discount' => $lineDiscount, 'tax' => $lineTax, 'line_total' => $lineTotal, 'created_at' => now(), 'updated_at' => now()]);
        }
        DB::table('disnew_sales_orders')->where('id', $orderId)->update(['subtotal' => $subtotal, 'discount_total' => $discount, 'tax_total' => $tax, 'grand_total' => $grand, 'updated_at' => now()]);
    }
}
