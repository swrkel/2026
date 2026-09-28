<?php
namespace Modules\AutoService\Services;

use Illuminate\Support\Facades\DB;
use Modules\AutoService\Entities\AutoServiceInvoice;
use Modules\AutoService\Entities\AutoServiceInvoiceLine;
use Modules\AutoService\Entities\AutoServiceJob;
use Modules\AutoService\Entities\AutoServiceTimeline;

class AutoServiceInvoiceService
{
    public function saveInvoice(array $data, array $lines = [])
    {
        return DB::transaction(function () use ($data, $lines) {
            $invoice = isset($data['id']) ? AutoServiceInvoice::findOrFail($data['id']) : new AutoServiceInvoice();
            $invoice->fill($data);
            if (!$invoice->invoice_no) $invoice->invoice_no = app(AutoServiceNumberService::class)->nextInvoiceNo($data['business_id'] ?? null);
            $totals = $this->calculateTotals($lines, $data);
            $invoice->subtotal = $totals['subtotal'];
            $invoice->discount_amount = $totals['discount'];
            $invoice->tax_amount = $totals['tax'];
            $invoice->total_amount = $totals['total'];
            $invoice->paid_amount = (float)($data['paid_amount'] ?? $invoice->paid_amount ?? 0);
            $invoice->balance_amount = max(0, $invoice->total_amount - $invoice->paid_amount);
            if ($invoice->balance_amount <= 0 && $invoice->total_amount > 0) $invoice->status = 'paid';
            if ($invoice->balance_amount > 0 && $invoice->paid_amount > 0) $invoice->status = 'partial';
            $invoice->save();

            AutoServiceInvoiceLine::where('invoice_id', $invoice->id)->delete();
            foreach ($lines as $line) {
                if (empty($line['description'])) continue;
                $qty = (float)($line['quantity'] ?? 1);
                $unit = (float)($line['unit_price'] ?? 0);
                AutoServiceInvoiceLine::create([
                    'business_id' => $invoice->business_id,
                    'invoice_id' => $invoice->id,
                    'line_type' => $line['line_type'] ?? 'service',
                    'component_type' => $line['component_type'] ?? null,
                    'package_id' => $line['package_id'] ?? null,
                    'package_line_id' => $line['package_line_id'] ?? null,
                    'variation_id' => $line['variation_id'] ?? null,
                    'is_stock_item' => !empty($line['is_stock_item']) ? 1 : 0,
                    'product_id' => $line['product_id'] ?? null,
                    'description' => $line['description'],
                    'quantity' => $qty,
                    'unit_price' => $unit,
                    'line_total' => $qty * $unit,
                ]);
            }

            if ($invoice->job_id) {
                AutoServiceJob::where('id', $invoice->job_id)->update([
                    'status' => $invoice->balance_amount <= 0 ? 'invoiced_paid' : 'invoiced',
                    'total_amount' => $invoice->total_amount,
                    'paid_amount' => $invoice->paid_amount,
                    'balance_amount' => $invoice->balance_amount,
                ]);
            }

            if ($invoice->vehicle_id) {
                AutoServiceTimeline::create([
                    'business_id' => $invoice->business_id,
                    'location_id' => $invoice->location_id,
                    'vehicle_id' => $invoice->vehicle_id,
                    'job_id' => $invoice->job_id,
                    'event_type' => 'invoice_saved',
                    'title' => 'Invoice Saved',
                    'description' => 'Invoice '.$invoice->invoice_no.' saved with total '.number_format((float)$invoice->total_amount, 2),
                    'event_at' => now(),
                ]);
            }
            return $invoice;
        });
    }

    public function makeFromJob(AutoServiceJob $job)
    {
        $lines = [];
        foreach ($job->lines as $line) {
            $lines[] = $line->only(['line_type','component_type','package_id','package_line_id','product_id','variation_id','is_stock_item','description','quantity','unit_price']);
        }
        return $this->saveInvoice([
            'business_id' => $job->business_id,
            'location_id' => $job->location_id,
            'contact_id' => $job->contact_id,
            'vehicle_id' => $job->vehicle_id,
            'job_id' => $job->id,
            'invoice_date' => date('Y-m-d'),
            'status' => 'draft',
            'discount_amount' => $job->discount_amount,
            'tax_amount' => $job->tax_amount,
            'paid_amount' => $job->paid_amount,
        ], $lines);
    }

    public function calculateTotals(array $lines, array $data)
    {
        $subtotal = 0;
        foreach ($lines as $line) $subtotal += ((float)($line['quantity'] ?? 1)) * ((float)($line['unit_price'] ?? 0));
        $discount = (float)($data['discount_amount'] ?? 0);
        $tax = (float)($data['tax_amount'] ?? 0);
        $total = max(0, $subtotal - $discount + $tax);
        return compact('subtotal','discount','tax','total');
    }
}
