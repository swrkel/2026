<?php

namespace Modules\DistributionNew\Services;

use Illuminate\Support\Facades\DB;
use Modules\DistributionNew\Utils\DisnewNumberUtil;

class DisnewInvoiceService
{
    public function createFromOrder(int $salesOrderId): int
    {
        return DB::transaction(function () use ($salesOrderId) {
            $order = DB::table('disnew_sales_orders')->where('id', $salesOrderId)->lockForUpdate()->first();
            abort_if(!$order, 404, 'Sales order not found');
            $invoiceNo = DisnewNumberUtil::next('sales_invoice', 'DSI-', $order->business_id);
            $invoiceId = DB::table('disnew_sales_invoices')->insertGetId([
                'business_id' => $order->business_id,
                'location_id' => $order->location_id,
                'sales_order_id' => $order->id,
                'invoice_no' => $invoiceNo,
                'invoice_date' => now()->toDateString(),
                'customer_id' => $order->customer_id,
                'sales_rep_id' => $order->sales_rep_id,
                'status' => 'issued',
                'subtotal' => $order->subtotal,
                'discount_total' => $order->discount_total,
                'tax_total' => $order->tax_total,
                'grand_total' => $order->grand_total,
                'created_by' => auth()->id(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $lines = DB::table('disnew_sales_order_lines')->where('sales_order_id', $order->id)->get();
            foreach ($lines as $line) {
                DB::table('disnew_sales_invoice_lines')->insert([
                    'sales_invoice_id' => $invoiceId,
                    'sales_order_line_id' => $line->id,
                    'product_id' => $line->product_id,
                    'product_name' => $line->product_name,
                    'qty' => $line->qty,
                    'unit_price' => $line->unit_price,
                    'discount' => $line->discount,
                    'tax' => $line->tax,
                    'line_total' => $line->line_total,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            DB::table('disnew_sales_orders')->where('id', $order->id)->update(['status' => 'invoiced', 'updated_at' => now()]);
            app(DisnewSmsService::class)->queue($order->business_id, $order->location_id, 'invoice_created', $order->customer_id, null, 'Sales invoice '.$invoiceNo.' has been created from order '.$order->sales_order_no.'.', ['sales_order_id' => $order->id, 'invoice_id' => $invoiceId]);
            return $invoiceId;
        });
    }
}
