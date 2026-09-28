<?php
namespace Modules\AutoService\Services;

use Illuminate\Support\Facades\DB;
use Modules\AutoService\Entities\AutoServiceEstimate;
use Modules\AutoService\Entities\AutoServiceJob;
use Modules\AutoService\Entities\AutoServiceJobLine;
use Modules\AutoService\Entities\AutoServiceTimeline;

class AutoServiceEstimateToJobService
{
    public function convert(int $estimateId, ?int $userId = null): AutoServiceJob
    {
        return DB::transaction(function () use ($estimateId, $userId) {
            $estimate = AutoServiceEstimate::with('lines')->lockForUpdate()->findOrFail($estimateId);

            if (!empty($estimate->job_id)) {
                return AutoServiceJob::with('lines')->findOrFail($estimate->job_id);
            }

            $job = new AutoServiceJob();
            $job->business_id = $estimate->business_id;
            $job->location_id = $estimate->location_id;
            $job->contact_id = $estimate->contact_id;
            $job->vehicle_id = $estimate->vehicle_id;
            $job->job_no = app(AutoServiceNumberService::class)->nextJobNo($estimate->business_id);
            $job->job_date = date('Y-m-d');
            $job->job_type = 'estimate_conversion';
            $job->status = 'opened';
            $job->customer_complaint = $estimate->customer_complaint;
            $job->advisor_notes = trim(($estimate->advisor_notes ?? '') . "
Converted from estimate " . ($estimate->estimate_no ?? $estimate->id));
            $job->discount_amount = (float) $estimate->discount_amount;
            $job->tax_amount = (float) $estimate->tax_amount;
            $job->subtotal = (float) $estimate->subtotal;
            $job->total_amount = (float) $estimate->total_amount;
            $job->paid_amount = 0;
            $job->balance_amount = (float) $estimate->total_amount;
            $job->save();

            foreach ($estimate->lines as $line) {
                AutoServiceJobLine::create([
                    'business_id' => $job->business_id,
                    'job_id' => $job->id,
                    'line_type' => $line->line_type ?: 'service',
                    'product_id' => $line->product_id,
                    'description' => $line->description,
                    'quantity' => $line->quantity,
                    'unit_price' => $line->unit_price,
                    'line_total' => $line->line_total,
                ]);
            }

            $estimate->status = 'converted';
            $estimate->approved_at = $estimate->approved_at ?: now();
            $estimate->approved_by = $estimate->approved_by ?: $userId;
            $estimate->job_id = $job->id;
            $estimate->save();

            AutoServiceTimeline::create([
                'business_id' => $job->business_id,
                'location_id' => $job->location_id,
                'vehicle_id' => $job->vehicle_id,
                'job_id' => $job->id,
                'event_type' => 'estimate_converted',
                'title' => 'Estimate Converted to Job',
                'description' => 'Estimate '.($estimate->estimate_no ?: $estimate->id).' converted into job '.$job->job_no.'.',
                'event_at' => now(),
            ]);

            return $job;
        });
    }
}
