<?php
namespace Modules\DistributionNew\Services;

use Illuminate\Support\Facades\DB;
use Modules\DistributionNew\Models\DisnewInvoiceAllocation;
use Modules\DistributionNew\Models\DisnewSalesOrder;

class DisnewInvoiceAllocationService
{
    public function allocateFromOrder(DisnewSalesOrder $order, array $invoiceLines, int $invoiceId, ?int $userId = null): void
    {
        DB::transaction(function () use ($order, $invoiceLines, $invoiceId, $userId) {
            foreach ($invoiceLines as $line) {
                $orderedQty = (float)($line['ordered_qty'] ?? 0);
                $invoiceQty = (float)($line['qty'] ?? 0);
                $already = (float) DisnewInvoiceAllocation::where('sales_order_line_id', $line['sales_order_line_id'] ?? 0)->sum('invoiced_qty');
                if (($already + $invoiceQty) > $orderedQty) {
                    throw new \RuntimeException('Invoice quantity cannot exceed Sales Order remaining quantity.');
                }
                DisnewInvoiceAllocation::create([
                    'business_id' => $order->business_id,
                    'sales_order_id' => $order->id,
                    'sales_order_line_id' => $line['sales_order_line_id'] ?? null,
                    'sales_invoice_id' => $invoiceId,
                    'sales_invoice_line_id' => $line['sales_invoice_line_id'] ?? null,
                    'product_id' => $line['product_id'] ?? null,
                    'ordered_qty' => $orderedQty,
                    'invoiced_qty' => $invoiceQty,
                    'remaining_qty' => max(0, $orderedQty - $already - $invoiceQty),
                    'created_by' => $userId,
                ]);
            }
        });
    }
}
