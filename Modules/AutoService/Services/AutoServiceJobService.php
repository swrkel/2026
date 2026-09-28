<?php
namespace Modules\AutoService\Services;

use Illuminate\Support\Facades\DB;
use Modules\AutoService\Entities\AutoServiceJob;
use Modules\AutoService\Entities\AutoServiceJobLine;
use Modules\AutoService\Entities\AutoServicePayment;
use Modules\AutoService\Entities\AutoServiceReminder;
use Modules\AutoService\Entities\AutoServiceTimeline;
use Modules\AutoService\Entities\AutoServiceVehicle;

class AutoServiceJobService
{
    public function saveJob(array $data, array $lines = [], array $payments = [])
    {
        return DB::transaction(function () use ($data, $lines, $payments) {
            $job = isset($data['id']) ? AutoServiceJob::findOrFail($data['id']) : new AutoServiceJob();
            $job->fill($data);
            if (!$job->job_no) $job->job_no = app(AutoServiceNumberService::class)->nextJobNo($data['business_id'] ?? null);
            $totals = $this->calculateTotals($lines, $payments, $data);
            $job->subtotal = $totals['subtotal'];
            $job->discount_amount = $totals['discount'];
            $job->tax_amount = $totals['tax'];
            $job->total_amount = $totals['total'];
            $job->paid_amount = $totals['paid'];
            $job->balance_amount = $totals['balance'];
            $job->save();

            AutoServiceJobLine::where('job_id', $job->id)->delete();
            foreach ($lines as $line) {
                if (empty($line['description'])) continue;
                $qty = (float)($line['quantity'] ?? 1);
                $unit = (float)($line['unit_price'] ?? 0);
                AutoServiceJobLine::create([
                    'business_id' => $job->business_id,
                    'job_id' => $job->id,
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

            if (!empty($payments)) {
                AutoServicePayment::where('job_id', $job->id)->delete();
                foreach ($payments as $payment) {
                    if (empty($payment['amount'])) continue;
                    AutoServicePayment::create([
                        'business_id' => $job->business_id,
                        'job_id' => $job->id,
                        'payment_date' => $payment['payment_date'] ?? date('Y-m-d'),
                        'payment_method' => $payment['payment_method'] ?? 'cash',
                        'reference_no' => $payment['reference_no'] ?? null,
                        'amount' => $payment['amount'],
                        'note' => $payment['note'] ?? null,
                    ]);
                }
            }

            $this->timeline($job, 'job_saved', 'Job Saved', 'Auto Service job saved/updated.');
            $this->syncReminder($job);
            return $job;
        });
    }

    public function calculateTotals(array $lines, array $payments, array $data)
    {
        $subtotal = 0;
        foreach ($lines as $line) $subtotal += ((float)($line['quantity'] ?? 1)) * ((float)($line['unit_price'] ?? 0));
        $discount = (float)($data['discount_amount'] ?? 0);
        $tax = (float)($data['tax_amount'] ?? 0);
        $paid = 0;
        foreach ($payments as $p) $paid += (float)($p['amount'] ?? 0);
        $total = max(0, $subtotal - $discount + $tax);
        return compact('subtotal','discount','tax','total','paid') + ['balance' => max(0, $total - $paid)];
    }

    public function timeline(AutoServiceJob $job, $type, $title, $description = null)
    {
        AutoServiceTimeline::create([
            'business_id' => $job->business_id,
            'location_id' => $job->location_id,
            'vehicle_id' => $job->vehicle_id,
            'job_id' => $job->id,
            'event_type' => $type,
            'title' => $title,
            'description' => $description,
            'event_at' => now(),
        ]);
    }

    public function syncReminder(AutoServiceJob $job)
    {
        if (!$job->next_service_date || !$job->vehicle_id) return;
        $days = (int) (DB::table('auto_service_settings')->where('business_id',$job->business_id)->where('key','sms_reminder_days_before')->value('value') ?: config('autoservice.default_reminder_days_before', 7));
        $vehicle = AutoServiceVehicle::find($job->vehicle_id);
        $mobile = null;
        if ($job->contact_id && \Schema::hasTable('contacts')) $mobile = DB::table('contacts')->where('id',$job->contact_id)->value('mobile');
        AutoServiceReminder::updateOrCreate(['job_id'=>$job->id, 'vehicle_id'=>$job->vehicle_id], [
            'business_id' => $job->business_id,
            'contact_id' => $job->contact_id,
            'due_date' => $job->next_service_date,
            'days_before' => $days,
            'send_on' => date('Y-m-d', strtotime($job->next_service_date . " -{$days} days")),
            'channel' => 'sms',
            'mobile' => $mobile,
            'message' => 'Reminder: your vehicle ' . ($vehicle->registration_no ?? '') . ' is due for next service on ' . $job->next_service_date . '.',
            'status' => 'pending',
        ]);
    }
}
