<?php
namespace Modules\AutoService\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\AutoService\Entities\AutoServiceInvoice;
use Modules\AutoService\Entities\AutoServiceJob;
use Modules\AutoService\Entities\AutoServicePayment;
use Modules\AutoService\Entities\AutoServiceTimeline;

class AutoServiceBillingDeliveryService
{
    public function deliveryBoard($businessId, $locationId = null, array $filters = [])
    {
        $q = AutoServiceJob::query()->with(['lines', 'payments']);
        if ($businessId) $q->where('business_id', $businessId);
        if ($locationId && Schema::hasColumn('auto_service_jobs', 'location_id')) $q->where('location_id', $locationId);
        if (!empty($filters['status'])) $q->where('status', $filters['status']);
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $q->where(function ($sub) use ($search) {
                $sub->where('job_no', 'like', '%'.$search.'%')->orWhere('id', $search);
            });
        }
        return $q->whereIn('status', ['qc_passed', 'ready_for_delivery', 'completed', 'invoiced', 'partial', 'invoiced_paid'])
            ->orderByDesc('id')
            ->paginate(25);
    }

    public function summary($businessId, $locationId = null)
    {
        $job = AutoServiceJob::query();
        $inv = AutoServiceInvoice::query();
        if ($businessId) { $job->where('business_id', $businessId); $inv->where('business_id', $businessId); }
        if ($locationId && Schema::hasColumn('auto_service_jobs', 'location_id')) $job->where('location_id', $locationId);
        if ($locationId && Schema::hasColumn('auto_service_invoices', 'location_id')) $inv->where('location_id', $locationId);
        return [
            'ready' => (clone $job)->whereIn('status', ['qc_passed', 'ready_for_delivery', 'completed'])->count(),
            'invoiced' => (clone $job)->whereIn('status', ['invoiced', 'partial', 'invoiced_paid'])->count(),
            'unpaid' => (clone $inv)->where('balance_amount', '>', 0)->sum('balance_amount'),
            'paid_today' => Schema::hasTable('auto_service_payments') ? DB::table('auto_service_payments')->when($businessId, function ($q) use ($businessId) { return $q->where('business_id', $businessId); })->whereDate('payment_date', date('Y-m-d'))->sum('amount') : 0,
        ];
    }

    public function generateInvoiceForCompletedJob(AutoServiceJob $job, $businessId, $locationId = null)
    {
        $this->guardScope($job, $businessId, $locationId);
        $existing = AutoServiceInvoice::where('job_id', $job->id)->first();
        if ($existing) return $existing;
        $invoice = app(AutoServiceInvoiceService::class)->makeFromJob($job);
        $this->timeline($job, 'invoice_generated', 'Invoice Generated', 'Invoice '.$invoice->invoice_no.' generated from job card.');
        return $invoice;
    }

    public function recordPayment(AutoServiceInvoice $invoice, array $data, $businessId, $locationId = null)
    {
        return DB::transaction(function () use ($invoice, $data, $businessId, $locationId) {
            if ($businessId && (int)$invoice->business_id !== (int)$businessId) abort(403, 'Invoice does not belong to this business.');
            if ($locationId && Schema::hasColumn('auto_service_invoices', 'location_id') && (int)$invoice->location_id !== (int)$locationId) abort(403, 'Invoice does not belong to this location.');
            $amount = (float)($data['amount'] ?? $invoice->balance_amount ?? 0);
            if ($amount <= 0) abort(422, 'Payment amount must be greater than zero.');
            AutoServicePayment::create([
                'business_id' => $invoice->business_id,
                'location_id' => $invoice->location_id ?? null,
                'job_id' => $invoice->job_id,
                'invoice_id' => $invoice->id,
                'payment_date' => $data['payment_date'] ?? date('Y-m-d'),
                'method' => $data['method'] ?? 'cash',
                'amount' => $amount,
                'reference_no' => $data['reference_no'] ?? null,
                'note' => $data['note'] ?? null,
            ]);
            $invoice->paid_amount = (float)$invoice->paid_amount + $amount;
            $invoice->balance_amount = max(0, (float)$invoice->total_amount - (float)$invoice->paid_amount);
            $invoice->status = $invoice->balance_amount <= 0 ? 'paid' : 'partial';
            $invoice->save();
            if ($invoice->job_id) {
                AutoServiceJob::where('id', $invoice->job_id)->update([
                    'paid_amount' => $invoice->paid_amount,
                    'balance_amount' => $invoice->balance_amount,
                    'status' => $invoice->balance_amount <= 0 ? 'invoiced_paid' : 'partial',
                ]);
                $job = AutoServiceJob::find($invoice->job_id);
                if ($job) $this->timeline($job, 'payment_recorded', 'Payment Recorded', 'Payment of '.number_format($amount, 2).' recorded for invoice '.$invoice->invoice_no.'.');
            }
            return $invoice;
        });
    }

    public function releaseVehicle(AutoServiceJob $job, array $data, $businessId, $locationId = null)
    {
        $this->guardScope($job, $businessId, $locationId);
        $invoice = AutoServiceInvoice::where('job_id', $job->id)->latest()->first();
        if (!$invoice) abort(422, 'Generate invoice before vehicle release.');
        if ((float)$invoice->balance_amount > 0 && empty($data['allow_credit_release'])) abort(422, 'Invoice has balance. Tick credit release if approved.');
        $job->status = 'delivered';
        $job->delivered_at = now();
        $job->delivery_note = $data['delivery_note'] ?? null;
        if (Schema::hasColumn('auto_service_jobs', 'customer_signature')) $job->customer_signature = $data['customer_signature'] ?? null;
        if (Schema::hasColumn('auto_service_jobs', 'warranty_note')) $job->warranty_note = $data['warranty_note'] ?? null;
        $job->save();
        $this->timeline($job, 'vehicle_delivered', 'Vehicle Delivered', 'Vehicle released to customer. '.($job->delivery_note ?: ''));
        return $job;
    }

    protected function guardScope(AutoServiceJob $job, $businessId, $locationId = null)
    {
        if ($businessId && (int)$job->business_id !== (int)$businessId) abort(403, 'Job does not belong to this business.');
        if ($locationId && Schema::hasColumn('auto_service_jobs', 'location_id') && (int)$job->location_id !== (int)$locationId) abort(403, 'Job does not belong to this location.');
    }

    protected function timeline(AutoServiceJob $job, $type, $title, $description)
    {
        AutoServiceTimeline::create([
            'business_id' => $job->business_id,
            'location_id' => $job->location_id ?? null,
            'vehicle_id' => $job->vehicle_id,
            'job_id' => $job->id,
            'event_type' => $type,
            'title' => $title,
            'description' => $description,
            'event_at' => now(),
        ]);
    }
}
