<?php

namespace Modules\DistributionNew\Services\Invoices;

use Illuminate\Support\Facades\DB;
use Modules\DistributionNew\Services\DisnewCustomerLookupService;
use Modules\DistributionNew\Services\Sms\DisnewSmsEventService;
use Modules\DistributionNew\Utils\DisnewNumberUtil;

class DisnewInvoiceFromOrderService
{
    public function __construct(
        protected DisnewNumberUtil $numbers,
        protected DisnewCustomerLookupService $customers,
        protected DisnewSmsEventService $sms
    ) {}

    public function createFromOrder(int $orderId, int $businessId, ?int $userId): int
    {
        return DB::transaction(function () use ($orderId, $businessId, $userId) {
            $order = DB::table('disnew_sales_orders')->where('business_id', $businessId)->where('id', $orderId)->first();
            if (!$order) { abort(404); }
            if (in_array($order->status, ['cancelled','delivered'], true)) { abort(403, 'Cannot invoice this sales order status.'); }

            $existing = DB::table('disnew_sales_invoices')->where('sales_order_id', $orderId)->where('status', '!=', 'cancelled')->first();
            if ($existing) { return (int)$existing->id; }

            $invoiceNo = $this->numbers->next($businessId, 'sales_invoice', 'DNI');
            $invoiceId = DB::table('disnew_sales_invoices')->insertGetId([
                'business_id'=>$businessId,'location_id'=>$order->location_id,'sales_order_id'=>$order->id,'invoice_no'=>$invoiceNo,
                'invoice_date'=>now()->toDateString(),'customer_id'=>$order->customer_id,'sales_rep_id'=>$order->sales_rep_id,
                'status'=>'issued','subtotal'=>$order->subtotal,'discount_total'=>$order->discount_total,'tax_total'=>$order->tax_total,
                'grand_total'=>$order->grand_total,'source_type'=>'sales_order','created_by'=>$userId,'created_at'=>now(),'updated_at'=>now(),
            ]);

            $lines = DB::table('disnew_sales_order_lines')->where('sales_order_id', $orderId)->get();
            foreach ($lines as $line) {
                DB::table('disnew_sales_invoice_lines')->insert([
                    'sales_invoice_id'=>$invoiceId,'sales_order_line_id'=>$line->id,'product_id'=>$line->product_id,'product_name'=>$line->product_name,
                    'qty'=>$line->qty,'unit_price'=>$line->unit_price,'discount'=>$line->discount,'tax'=>$line->tax,'line_total'=>$line->line_total,
                    'created_at'=>now(),'updated_at'=>now(),
                ]);
            }

            DB::table('disnew_sales_orders')->where('id', $orderId)->update(['status'=>'invoiced','updated_at'=>now()]);
            $customer = $this->customers->find($businessId, $order->customer_id);
            $this->sms->fire($businessId, $order->location_id, 'sales_invoice_created', $order->customer_id, $customer['mobile'] ?? null, [
                'number'=>$invoiceNo,'order_number'=>$order->sales_order_no,'amount'=>number_format((float)$order->grand_total, 2),'customer'=>$customer['name'] ?? '',
            ]);
            DB::table('disnew_sales_invoices')->where('id', $invoiceId)->update(['sms_sent_at'=>now(), 'updated_at'=>now()]);
            return $invoiceId;
        });
    }
}
