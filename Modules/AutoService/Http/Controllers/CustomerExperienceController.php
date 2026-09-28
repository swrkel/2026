<?php

namespace Modules\AutoService\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\AutoService\Entities\AutoServiceDocument;
use Modules\AutoService\Entities\AutoServiceInvoice;
use Modules\AutoService\Entities\AutoServiceJob;
use Modules\AutoService\Entities\AutoServiceTimeline;
use Modules\AutoService\Entities\AutoServiceVehicle;

class CustomerExperienceController extends AutoServiceBaseController
{
    /**
     * Live progress endpoint for customer portal widgets.
     * Public route, but guarded by vehicle/job matching keyword to avoid exposing unrelated data.
     */
    public function liveProgress(Request $request)
    {
        $job = AutoServiceJob::findOrFail((int) $request->get('job_id'));
        $vehicle = AutoServiceVehicle::find($job->vehicle_id);

        if (!$this->isCustomerRequestAllowed($request, $job, $vehicle)) {
            abort(403, 'This service progress is not available for the supplied customer reference.');
        }

        $timeline = AutoServiceTimeline::where('job_id', $job->id)
            ->orderByDesc('event_at')
            ->orderByDesc('id')
            ->limit(10)
            ->get(['event_at', 'event_type', 'title', 'description']);

        $invoice = AutoServiceInvoice::where('job_id', $job->id)->orderByDesc('id')->first();

        return response()->json([
            'job_id' => $job->id,
            'job_no' => $job->job_no,
            'status' => $job->status,
            'workflow_stage' => $job->workflow_stage ?? $job->status,
            'status_label' => $this->statusLabel($job->status),
            'progress_percent' => $this->progressPercent($job->status),
            'estimated_completion_at' => $job->estimated_completion_at ?? $job->estimated_delivery_at,
            'customer_visible_note' => $job->customer_visible_note,
            'invoice_no' => $invoice->invoice_no ?? null,
            'bill_total' => (float)($invoice->grand_total ?? $job->grand_total ?? $job->total_amount ?? 0),
            'paid_amount' => (float)($invoice->paid_amount ?? $job->paid_amount ?? 0),
            'balance_amount' => (float)($invoice->balance_amount ?? $job->balance_amount ?? 0),
            'timeline' => $timeline,
        ]);
    }

    public function requestCallback(Request $request)
    {
        $data = $request->validate([
            'q' => 'nullable|string|max:191',
            'job_id' => 'nullable|integer',
            'vehicle_id' => 'required|integer',
            'customer_name' => 'required|string|max:191',
            'customer_mobile' => 'required|string|max:50',
            'preferred_time' => 'nullable|string|max:100',
            'reason' => 'nullable|string|max:191',
            'message' => 'nullable|string|max:2000',
        ]);

        $vehicle = AutoServiceVehicle::findOrFail($data['vehicle_id']);
        $job = !empty($data['job_id']) ? AutoServiceJob::find($data['job_id']) : null;

        if (!$this->isCustomerRequestAllowed($request, $job, $vehicle)) {
            abort(403, 'This vehicle/job is not available in the customer portal.');
        }

        DB::table('auto_service_customer_callback_requests')->insert([
            'business_id' => $this->businessId(),
            'location_id' => $this->locationId(),
            'job_id' => $job->id ?? null,
            'vehicle_id' => $vehicle->id,
            'contact_id' => $vehicle->contact_id ?? null,
            'customer_name' => $data['customer_name'],
            'customer_mobile' => $data['customer_mobile'],
            'preferred_time' => $data['preferred_time'] ?? null,
            'reason' => $data['reason'] ?? 'service_update',
            'message' => $data['message'] ?? null,
            'status' => 'requested',
            'requested_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        AutoServiceTimeline::create([
            'business_id' => $this->businessId(),
            'job_id' => $job->id ?? null,
            'vehicle_id' => $vehicle->id,
            'event_at' => now(),
            'event_type' => 'customer_callback_requested',
            'title' => 'Customer Callback Requested',
            'description' => 'Customer requested a callback from the portal. Reason: ' . ($data['reason'] ?? 'service_update'),
            'created_by' => auth()->id(),
        ]);

        if (DB::getSchemaBuilder()->hasTable('auto_service_customer_portal_alerts')) {
            DB::table('auto_service_customer_portal_alerts')->insert([
                'business_id' => $this->businessId(),
                'location_id' => $this->locationId(),
                'job_id' => $job->id ?? null,
                'vehicle_id' => $vehicle->id,
                'alert_type' => 'callback_requested',
                'title' => 'Callback Request Received',
                'message' => 'Your callback request has been received by the service team.',
                'priority' => 'normal',
                'status' => 'unread',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return back()->with('status', 'Your callback request has been submitted. The service advisor will contact you.');
    }

    public function jobCard(Request $request, $jobId)
    {
        $job = AutoServiceJob::with(['partMovements', 'mechanics'])->findOrFail($jobId);
        $vehicle = AutoServiceVehicle::find($job->vehicle_id);
        if (!$this->isCustomerRequestAllowed($request, $job, $vehicle)) {
            abort(403, 'This job card is not available in the customer portal.');
        }
        $timeline = AutoServiceTimeline::where('job_id', $job->id)->orderBy('event_at')->get();
        $documents = AutoServiceDocument::where('job_id', $job->id)->where('visible_to_customer', 1)->get();
        return view('autoservice::customer_experience.job_card_print', compact('job', 'vehicle', 'timeline', 'documents'));
    }

    public function inspectionReport(Request $request, $jobId)
    {
        $job = AutoServiceJob::findOrFail($jobId);
        $vehicle = AutoServiceVehicle::find($job->vehicle_id);
        if (!$this->isCustomerRequestAllowed($request, $job, $vehicle)) {
            abort(403, 'This inspection report is not available in the customer portal.');
        }
        $inspections = DB::table('auto_service_inspections')->where('job_id', $job->id)->orderByDesc('id')->get();
        $inspectionItems = DB::table('auto_service_inspection_items')->where('job_id', $job->id)->orderBy('id')->get();
        return view('autoservice::customer_experience.inspection_report', compact('job', 'vehicle', 'inspections', 'inspectionItems'));
    }

    public function warrantyCertificate(Request $request, $jobId)
    {
        $job = AutoServiceJob::findOrFail($jobId);
        $vehicle = AutoServiceVehicle::find($job->vehicle_id);
        if (!$this->isCustomerRequestAllowed($request, $job, $vehicle)) {
            abort(403, 'This warranty certificate is not available in the customer portal.');
        }
        $warranties = DB::table('auto_service_warranty_claims')->where('job_id', $job->id)->orderByDesc('id')->get();
        $invoice = AutoServiceInvoice::where('job_id', $job->id)->orderByDesc('id')->first();
        return view('autoservice::customer_experience.warranty_certificate', compact('job', 'vehicle', 'warranties', 'invoice'));
    }

    protected function isCustomerRequestAllowed(Request $request, $job, $vehicle): bool
    {
        if (!$vehicle) { return false; }
        if ($job && (int)$job->vehicle_id !== (int)$vehicle->id) { return false; }
        $keyword = strtolower(trim((string)$request->get('q', '')));
        if ($keyword === '') { return true; }
        $checks = [
            strtolower((string)($vehicle->registration_no ?? '')),
            strtolower((string)($vehicle->vin ?? '')),
            strtolower((string)($vehicle->chassis_no ?? '')),
            strtolower((string)($vehicle->engine_no ?? '')),
            strtolower((string)($job->job_no ?? '')),
        ];
        foreach ($checks as $value) {
            if ($value !== '' && str_contains($value, $keyword)) { return true; }
        }
        return false;
    }

    protected function statusLabel($status): string
    {
        return ucwords(str_replace('_', ' ', (string)$status));
    }

    protected function progressPercent($status): int
    {
        $map = [
            'received' => 10, 'inspection' => 20, 'estimate' => 30, 'waiting_customer_approval' => 40,
            'approved' => 50, 'waiting_parts' => 55, 'in_progress' => 65, 'repair' => 70,
            'quality_control' => 82, 'completed' => 90, 'invoiced' => 94, 'ready_for_delivery' => 96,
            'delivered' => 100, 'cancelled' => 0,
        ];
        return $map[$status] ?? 50;
    }
}
