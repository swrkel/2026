<?php
namespace Modules\AutoService\Services;

use Illuminate\Support\Facades\DB;
use Modules\AutoService\Entities\AutoServiceEstimate;
use Modules\AutoService\Entities\AutoServiceEstimateLine;
use Modules\AutoService\Entities\AutoServiceTimeline;

class AutoServiceEstimateService
{
    public function saveEstimate(array $data, array $lines = [])
    {
        return DB::transaction(function () use ($data, $lines) {
            $estimate = isset($data['id']) ? AutoServiceEstimate::findOrFail($data['id']) : new AutoServiceEstimate();
            $estimate->fill($data);
            if (!$estimate->estimate_no) $estimate->estimate_no = app(AutoServiceNumberService::class)->nextEstimateNo($data['business_id'] ?? null);
            $totals = $this->calculateTotals($lines, $data);
            $estimate->subtotal = $totals['subtotal'];
            $estimate->discount_amount = $totals['discount'];
            $estimate->tax_amount = $totals['tax'];
            $estimate->total_amount = $totals['total'];
            $estimate->save();

            AutoServiceEstimateLine::where('estimate_id', $estimate->id)->delete();
            foreach ($lines as $line) {
                if (empty($line['description'])) continue;
                $qty = (float)($line['quantity'] ?? 1);
                $unit = (float)($line['unit_price'] ?? 0);
                AutoServiceEstimateLine::create([
                    'business_id' => $estimate->business_id,
                    'estimate_id' => $estimate->id,
                    'line_type' => $line['line_type'] ?? 'service',
                    'product_id' => $line['product_id'] ?? null,
                    'description' => $line['description'],
                    'quantity' => $qty,
                    'unit_price' => $unit,
                    'line_total' => $qty * $unit,
                ]);
            }

            if ($estimate->vehicle_id) {
                AutoServiceTimeline::create([
                    'business_id' => $estimate->business_id,
                    'location_id' => $estimate->location_id,
                    'vehicle_id' => $estimate->vehicle_id,
                    'event_type' => 'estimate_saved',
                    'title' => 'Estimate Saved',
                    'description' => 'Estimate '.$estimate->estimate_no.' saved with total '.number_format((float)$estimate->total_amount, 2),
                    'event_at' => now(),
                ]);
            }
            return $estimate;
        });
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
