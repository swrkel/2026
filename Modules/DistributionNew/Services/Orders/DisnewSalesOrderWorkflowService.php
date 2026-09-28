<?php

namespace Modules\DistributionNew\Services\Orders;

use Illuminate\Support\Facades\DB;
use Modules\DistributionNew\Services\DisnewCustomerLookupService;
use Modules\DistributionNew\Services\Sms\DisnewSmsEventService;
use Modules\DistributionNew\Utils\DisnewNumberUtil;

class DisnewSalesOrderWorkflowService
{
    public function __construct(
        protected DisnewNumberUtil $numbers,
        protected DisnewCustomerLookupService $customers,
        protected DisnewSmsEventService $sms
    ) {}

    public function create(array $data, int $businessId, ?int $locationId, ?int $userId, string $source = 'user'): int
    {
        return DB::transaction(function () use ($data, $businessId, $locationId, $userId, $source) {
            $lines = $data['lines'] ?? [];
            $totals = $this->calculate($lines);
            $orderNo = $data['sales_order_no'] ?? $this->numbers->next($businessId, 'sales_order', 'DNO');

            $id = DB::table('disnew_sales_orders')->insertGetId(array_merge($totals, [
                'business_id' => $businessId,
                'location_id' => $locationId,
                'sales_order_no' => $orderNo,
                'source' => $source,
                'customer_id' => $data['customer_id'] ?? null,
                'sales_rep_id' => $data['sales_rep_id'] ?? null,
                'order_date' => $data['order_date'] ?? now()->toDateString(),
                'delivery_date' => $data['delivery_date'] ?? null,
                'status' => $data['status'] ?? 'draft',
                'note' => $data['note'] ?? null,
                'created_by' => $userId,
                'updated_by' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ]));

            $this->replaceLines($id, $lines);
            $this->history($businessId, $id, null, $data['status'] ?? 'draft', 'Created', $userId);
            $this->notify($businessId, $locationId, 'sales_order_created', $id);
            return $id;
        });
    }

    public function update(int $id, array $data, int $businessId, ?int $userId): void
    {
        DB::transaction(function () use ($id, $data, $businessId, $userId) {
            $order = DB::table('disnew_sales_orders')->where('business_id', $businessId)->where('id', $id)->first();
            if (!$order) { abort(404); }
            if (!$this->canEdit($order)) { abort(403, 'This sales order cannot be edited after invoice/final delivery without permission.'); }
            $lines = $data['lines'] ?? [];
            $totals = $this->calculate($lines);
            $newStatus = $data['status'] ?? $order->status;
            DB::table('disnew_sales_orders')->where('id', $id)->update(array_merge($totals, [
                'customer_id' => $data['customer_id'] ?? $order->customer_id,
                'sales_rep_id' => $data['sales_rep_id'] ?? $order->sales_rep_id,
                'delivery_date' => $data['delivery_date'] ?? $order->delivery_date,
                'status' => $newStatus,
                'note' => $data['note'] ?? $order->note,
                'updated_by' => $userId,
                'updated_at' => now(),
            ]));
            $this->replaceLines($id, $lines);
            if ($newStatus !== $order->status) {
                $this->history($businessId, $id, $order->status, $newStatus, $data['status_note'] ?? 'Updated', $userId);
            }
            $this->notify($businessId, $order->location_id, 'sales_order_updated', $id);
        });
    }

    public function canEdit(object $order): bool
    {
        return in_array($order->status, ['draft','confirmed','loaded','partially_invoiced'], true);
    }

    private function calculate(array $lines): array
    {
        $subtotal = $discount = $tax = $grand = 0;
        foreach ($lines as $line) {
            $qty = (float)($line['qty'] ?? 0);
            $price = (float)($line['unit_price'] ?? 0);
            $lineDiscount = (float)($line['discount'] ?? 0);
            $lineTax = (float)($line['tax'] ?? 0);
            $lineTotal = ($qty * $price) - $lineDiscount + $lineTax;
            $subtotal += $qty * $price; $discount += $lineDiscount; $tax += $lineTax; $grand += $lineTotal;
        }
        return ['subtotal'=>$subtotal, 'discount_total'=>$discount, 'tax_total'=>$tax, 'grand_total'=>$grand];
    }

    private function replaceLines(int $orderId, array $lines): void
    {
        DB::table('disnew_sales_order_lines')->where('sales_order_id', $orderId)->delete();
        foreach ($lines as $line) {
            $qty = (float)($line['qty'] ?? 0); $price = (float)($line['unit_price'] ?? 0);
            $discount = (float)($line['discount'] ?? 0); $tax = (float)($line['tax'] ?? 0);
            DB::table('disnew_sales_order_lines')->insert([
                'sales_order_id' => $orderId,
                'product_id' => $line['product_id'] ?? null,
                'product_name' => $line['product_name'] ?? null,
                'qty' => $qty,
                'unit_price' => $price,
                'discount' => $discount,
                'tax' => $tax,
                'line_total' => ($qty * $price) - $discount + $tax,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function history(int $businessId, int $orderId, ?string $old, string $new, ?string $note, ?int $userId): void
    {
        DB::table('disnew_sales_order_status_histories')->insert([
            'business_id'=>$businessId,'sales_order_id'=>$orderId,'old_status'=>$old,'new_status'=>$new,'note'=>$note,
            'changed_by'=>$userId,'created_at'=>now(),'updated_at'=>now(),
        ]);
    }

    private function notify(int $businessId, ?int $locationId, string $event, int $orderId): void
    {
        $order = DB::table('disnew_sales_orders')->where('id', $orderId)->first();
        $customer = $this->customers->find($businessId, $order->customer_id);
        $this->sms->fire($businessId, $locationId, $event, $order->customer_id, $customer['mobile'] ?? null, [
            'number' => $order->sales_order_no,
            'amount' => number_format((float)$order->grand_total, 2),
            'customer' => $customer['name'] ?? '',
        ]);
        DB::table('disnew_sales_orders')->where('id', $orderId)->update(['last_sms_sent_at'=>now(), 'updated_at'=>now()]);
    }
}
