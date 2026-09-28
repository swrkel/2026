<?php

namespace Modules\AutoService\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\AutoService\Entities\AutoServiceApprovalRequest;
use Modules\AutoService\Entities\AutoServiceAppointment;
use Modules\AutoService\Entities\AutoServiceDocument;
use Modules\AutoService\Entities\AutoServiceFeedback;
use Modules\AutoService\Entities\AutoServiceInvoice;
use Modules\AutoService\Entities\AutoServiceJob;
use Modules\AutoService\Entities\AutoServiceReminder;
use Modules\AutoService\Entities\AutoServiceTimeline;
use Modules\AutoService\Entities\AutoServiceVehicle;

class CustomerPortalController extends AutoServiceBaseController
{
    public function lookup(Request $request)
    {
        return $this->buildLookupResponse($request, 'autoservice::customer_portal.lookup');
    }

    public function customerLogin(Request $request)
    {
        return $this->buildLookupResponse($request, 'autoservice::customer_portal.login', true);
    }

    protected function buildLookupResponse(Request $request, string $view, bool $publicMode = false)
    {
        $keyword = trim((string) $request->get('q', $request->get('registration_no', '')));
        $jobs = collect();
        $vehicles = collect();
        $selectedJob = null;
        $selectedVehicle = null;
        $reminders = collect();
        $timeline = collect();
        $documents = collect();
        $approvals = collect();
        $invoices = collect();
        $feedback = collect();
        $portalAppointments = collect();
        $portalAlerts = collect();
        $portalCommunicationLog = collect();
        $customerVehicles = collect();
        $currentBill = null;
        $serviceHistory = collect();
        $partsHistory = collect();
        $partsSummary = ['qty' => 0, 'subtotal' => 0, 'discount' => 0, 'total' => 0];
        $portalSettings = $this->customerPortalSettings();
        $canViewCurrentInvoice = !empty($portalSettings['allow_customer_current_invoice_view']) || !empty($portalSettings['enable_customer_current_invoice_view']);
        $partFilters = $this->partFilters($request);

        if ($keyword !== '') {
            $jobs = $this->searchJobs($keyword);

            if ($jobs->isEmpty()) {
                $vehicles = $this->searchVehicles($keyword);
                if ($vehicles->isNotEmpty()) {
                    $vehicleIds = $vehicles->pluck('id')->all();
                    $jobs = AutoServiceJob::whereIn('vehicle_id', $vehicleIds)
                        ->orderByDesc('job_date')
                        ->orderByDesc('id')
                        ->limit(20)
                        ->get();
                }
            }

            if ($jobs->isNotEmpty()) {
                $selectedJob = $jobs->first();
                $selectedVehicle = AutoServiceVehicle::find($selectedJob->vehicle_id);
            } elseif ($vehicles->isNotEmpty()) {
                $selectedVehicle = $vehicles->first();
            }

            if ($selectedVehicle) {
                $reminders = AutoServiceReminder::where('vehicle_id', $selectedVehicle->id)
                    ->orderByDesc('due_date')
                    ->limit(10)
                    ->get();

                $timeline = AutoServiceTimeline::where('vehicle_id', $selectedVehicle->id)
                    ->when($selectedJob, function ($q) use ($selectedJob) {
                        $q->where(function ($qq) use ($selectedJob) {
                            $qq->whereNull('job_id')->orWhere('job_id', $selectedJob->id);
                        });
                    })
                    ->orderByDesc('event_at')
                    ->orderByDesc('id')
                    ->limit(25)
                    ->get();

                $documents = AutoServiceDocument::where('visible_to_customer', 1)
                    ->where(function ($q) use ($selectedVehicle, $selectedJob) {
                        $q->where('vehicle_id', $selectedVehicle->id);
                        if ($selectedJob) {
                            $q->orWhere('job_id', $selectedJob->id);
                        }
                    })
                    ->orderByDesc('id')
                    ->limit(20)
                    ->get();

                if ($selectedJob) {
                    $approvals = AutoServiceApprovalRequest::where('job_id', $selectedJob->id)
                        ->orderByDesc('id')
                        ->limit(20)
                        ->get();
                }

                $invoices = $this->customerInvoices($selectedVehicle, $selectedJob, $canViewCurrentInvoice);
                $currentBill = $this->currentBill($selectedVehicle, $selectedJob, $canViewCurrentInvoice);
                $serviceHistory = $this->serviceHistory($selectedVehicle, $selectedJob);
                $partsHistory = $this->partsHistory($selectedVehicle, $selectedJob, $partFilters);
                $partsSummary = $this->partsSummary($partsHistory);
                $customerVehicles = $this->customerVehicles($selectedVehicle, $selectedJob);
                $portalAppointments = $this->portalAppointments($selectedVehicle, $selectedJob);
                $portalAlerts = $this->portalAlerts($selectedVehicle, $selectedJob);
                $portalCommunicationLog = $this->portalCommunicationLog($selectedVehicle, $selectedJob);

                $feedback = AutoServiceFeedback::where(function ($q) use ($selectedVehicle, $selectedJob) {
                    $q->where('vehicle_id', $selectedVehicle->id);
                    if ($selectedJob) {
                        $q->orWhere('job_id', $selectedJob->id);
                    }
                })->orderByDesc('submitted_at')->orderByDesc('id')->limit(20)->get();
            }
        }

        $statusSteps = $this->statusSteps();

        return view($view, compact(
            'keyword',
            'jobs',
            'vehicles',
            'selectedJob',
            'selectedVehicle',
            'reminders',
            'timeline',
            'documents',
            'approvals',
            'invoices',
            'canViewCurrentInvoice',
            'portalSettings',
            'customerVehicles',
            'feedback',
            'portalAppointments',
            'portalAlerts',
            'portalCommunicationLog',
            'statusSteps',
            'publicMode',
            'currentBill',
            'serviceHistory',
            'partsHistory',
            'partsSummary',
            'partFilters'
        ));
    }

    public function invoiceDetail(Request $request, $invoiceId)
    {
        $invoice = AutoServiceInvoice::with(['lines', 'payments', 'job'])->findOrFail($invoiceId);
        if (!$this->invoiceAllowedForPortal($invoice, $request)) {
            abort(403, 'This invoice is not available in the customer portal.');
        }
        $portalSettings = $this->customerPortalSettings();
        return view('autoservice::customer_portal.invoice_detail', compact('invoice', 'portalSettings'));
    }

    public function submitFeedback(Request $request)
    {
        if (empty($this->customerPortalSettings()['allow_customer_feedback'])) {
            abort(403, 'Customer feedback is disabled.');
        }
        $data = $request->validate([
            'job_id' => 'nullable|integer',
            'vehicle_id' => 'required|integer',
            'service_rating' => 'nullable|integer|min:1|max:5',
            'mechanic_rating' => 'nullable|integer|min:1|max:5',
            'workshop_rating' => 'nullable|integer|min:1|max:5',
            'customer_name' => 'nullable|string|max:191',
            'customer_mobile' => 'nullable|string|max:50',
            'comments' => 'nullable|string|max:2000',
        ]);
        $vehicle = AutoServiceVehicle::findOrFail($data['vehicle_id']);
        $data['contact_id'] = $vehicle->contact_id;
        $data['business_id'] = $this->businessId();
        $data['submitted_at'] = now();
        AutoServiceFeedback::create($data);
        return back()->with('status', 'Thank you. Your feedback has been recorded.');
    }

    public function uploadDocument(Request $request)
    {
        if (empty($this->customerPortalSettings()['allow_customer_document_upload'])) {
            abort(403, 'Customer document upload is disabled.');
        }

        $data = $request->validate([
            'q' => 'nullable|string|max:191',
            'job_id' => 'nullable|integer',
            'vehicle_id' => 'required|integer',
            'document_type' => 'required|string|max:100',
            'title' => 'nullable|string|max:191',
            'customer_name' => 'nullable|string|max:191',
            'customer_mobile' => 'nullable|string|max:50',
            'notes' => 'nullable|string|max:2000',
            'document_file' => 'required|file|max:10240|mimes:jpg,jpeg,png,pdf,doc,docx,webp',
        ]);

        $vehicle = AutoServiceVehicle::findOrFail($data['vehicle_id']);
        $job = !empty($data['job_id']) ? AutoServiceJob::find($data['job_id']) : null;

        if (!$this->portalVehicleJobMatch($vehicle, $job, $request)) {
            abort(403, 'This vehicle/job is not available in the customer portal.');
        }

        $file = $request->file('document_file');
        $folder = 'auto_service/customer_uploads/' . date('Y/m');
        $storedPath = $file->store($folder, 'public');

        $document = AutoServiceDocument::create([
            'business_id' => $this->businessId(),
            'job_id' => $job->id ?? null,
            'vehicle_id' => $vehicle->id,
            'document_type' => $data['document_type'],
            'document_group' => 'customer_upload',
            'title' => $data['title'] ?: Str::title(str_replace('_', ' ', $data['document_type'])),
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $storedPath,
            'mime_type' => $file->getClientMimeType(),
            'file_size' => $file->getSize(),
            'visible_to_customer' => 1,
            'customer_download_allowed' => 1,
            'uploaded_by_customer' => 1,
            'customer_name' => $data['customer_name'] ?? null,
            'customer_mobile' => $data['customer_mobile'] ?? null,
            'customer_upload_ip' => $request->ip(),
            'document_status' => 'submitted',
            'notes' => $data['notes'] ?? null,
            'created_by' => auth()->id(),
        ]);

        AutoServiceTimeline::create([
            'business_id' => $this->businessId(),
            'job_id' => $job->id ?? null,
            'vehicle_id' => $vehicle->id,
            'event_at' => now(),
            'event_type' => 'customer_document_uploaded',
            'title' => 'Customer Document Uploaded',
            'description' => ($document->title ?: 'Document') . ' was uploaded from the customer portal.',
            'created_by' => auth()->id(),
        ]);

$this->logPortalNotification('customer_document_uploaded', $job, $vehicle, 'Customer uploaded a document: ' . ($document->title ?: $document->file_name));
        $this->createPortalAlert($job, $vehicle, 'customer_document_uploaded', 'Document Received', 'Your uploaded document/photo has been received by the workshop.', 'normal');

        return back()->with('status', 'Your document/photo has been uploaded successfully.');
    }

    public function viewDocument(Request $request, $documentId)
    {
        $document = AutoServiceDocument::findOrFail($documentId);
        if (!$this->documentAllowedForPortal($document, $request)) {
            abort(403, 'This document is not available in the customer portal.');
        }
        if (empty($document->file_path) || !Storage::disk('public')->exists($document->file_path)) {
            abort(404, 'Document file not found.');
        }
        return Storage::disk('public')->response($document->file_path, $document->file_name ?: basename($document->file_path));
    }


    public function acknowledgeAlert(Request $request, $alertId)
    {
        if (!DB::getSchemaBuilder()->hasTable('auto_service_customer_portal_alerts')) {
            abort(404);
        }

        $alert = DB::table('auto_service_customer_portal_alerts')->where('id', $alertId)->first();
        if (!$alert) {
            abort(404);
        }

        $vehicle = !empty($alert->vehicle_id) ? AutoServiceVehicle::find($alert->vehicle_id) : null;
        $job = !empty($alert->job_id) ? AutoServiceJob::find($alert->job_id) : null;
        if ($vehicle && !$this->portalVehicleJobMatch($vehicle, $job, $request)) {
            abort(403, 'This alert is not available in the customer portal.');
        }

        DB::table('auto_service_customer_portal_alerts')->where('id', $alertId)->update([
            'acknowledged_at' => now(),
            'status' => 'acknowledged',
            'updated_at' => now(),
        ]);

        return back()->with('status', 'Alert marked as read.');
    }


    public function requestAppointment(Request $request)
    {
        if (empty($this->customerPortalSettings()['allow_customer_appointment_request'])) {
            abort(403, 'Customer appointment requests are disabled.');
        }

        $data = $request->validate([
            'vehicle_id' => 'nullable|integer',
            'registration_no' => 'nullable|string|max:100',
            'customer_name' => 'required|string|max:191',
            'customer_mobile' => 'required|string|max:50',
            'customer_email' => 'nullable|email|max:191',
            'preferred_date' => 'required|date',
            'preferred_time' => 'nullable|string|max:20',
            'service_type' => 'nullable|string|max:191',
            'customer_note' => 'nullable|string|max:2000',
        ]);

        $vehicle = !empty($data['vehicle_id']) ? AutoServiceVehicle::find($data['vehicle_id']) : null;
        if (!$vehicle && !empty($data['registration_no'])) {
            $vehicle = AutoServiceVehicle::where('registration_no', $data['registration_no'])->first();
        }

        $appointmentAt = trim($data['preferred_date'] . ' ' . ($data['preferred_time'] ?: '09:00'));
        $appointment = AutoServiceAppointment::create([
            'business_id' => $this->businessId(),
            'location_id' => $this->locationId(),
            'contact_id' => $vehicle->contact_id ?? null,
            'vehicle_id' => $vehicle->id ?? null,
            'appointment_no' => $this->nextPortalAppointmentNo(),
            'appointment_at' => $appointmentAt,
            'service_type' => $data['service_type'] ?? 'customer_request',
            'status' => 'requested',
            'customer_note' => trim(($data['customer_note'] ?? '') . "\n\nCustomer: " . $data['customer_name'] . "\nMobile: " . $data['customer_mobile'] . (!empty($data['customer_email']) ? "\nEmail: " . $data['customer_email'] : '')),
            'request_source' => 'customer_portal',
            'customer_name' => $data['customer_name'],
            'customer_mobile' => $data['customer_mobile'],
            'customer_email' => $data['customer_email'] ?? null,
        ]);

        if ($vehicle) {
            AutoServiceTimeline::create([
                'business_id' => $this->businessId(),
                'vehicle_id' => $vehicle->id,
                'event_at' => now(),
                'event_type' => 'customer_appointment_request',
                'title' => 'Customer Appointment Requested',
                'description' => 'Appointment request ' . $appointment->appointment_no . ' submitted from customer portal.',
                'created_by' => auth()->id(),
            ]);
        }

$this->createPortalAlert(null, $vehicle, 'appointment_requested', 'Appointment Request Submitted', 'Your appointment request has been received. The workshop will confirm the schedule.', 'normal');

        return back()->with('status', 'Your appointment request has been submitted. The workshop will confirm the schedule.');
    }

    public function respondApproval(Request $request, $approvalId)
    {
        $data = $request->validate([
            'response' => 'required|in:approved,declined',
            'customer_note' => 'nullable|string|max:2000',
            'q' => 'nullable|string|max:191',
        ]);

        $approval = AutoServiceApprovalRequest::findOrFail($approvalId);
        $job = AutoServiceJob::findOrFail($approval->job_id);
        $vehicle = AutoServiceVehicle::find($job->vehicle_id);

        if (!$this->approvalAllowedForPortal($approval, $job, $vehicle, $request)) {
            abort(403, 'This approval request is not available in the customer portal.');
        }

        if ($approval->status !== 'pending') {
            return back()->with('status', 'This approval request has already been responded.');
        }

        $approval->status = $data['response'];
        $approval->responded_at = now();
        $approval->customer_note = $data['customer_note'] ?? null;
        if (DB::getSchemaBuilder()->hasColumn('auto_service_approval_requests', 'customer_response_ip')) {
            $approval->customer_response_ip = $request->ip();
        }
        $approval->save();

        AutoServiceTimeline::create([
            'business_id' => $this->businessId(),
            'job_id' => $job->id,
            'vehicle_id' => $vehicle->id ?? null,
            'event_at' => now(),
            'event_type' => 'customer_approval_' . $data['response'],
            'title' => 'Customer ' . ucfirst($data['response']) . ' Approval',
            'description' => ($approval->approval_no ?: 'Approval request') . ' was ' . $data['response'] . ' from customer portal.',
            'created_by' => auth()->id(),
        ]);

$this->createPortalAlert($job, $vehicle, 'customer_approval_' . $data['response'], 'Approval Response Recorded', 'Your response was recorded as ' . $data['response'] . '.', $data['response'] === 'declined' ? 'high' : 'normal');

        return back()->with('status', 'Your approval response has been recorded.');
    }

    protected function portalVehicleJobMatch(AutoServiceVehicle $vehicle, $job, Request $request): bool
    {
        $keyword = trim((string) $request->get('q', ''));
        if ($job && (int) $job->vehicle_id !== (int) $vehicle->id) {
            return false;
        }
        if ($keyword === '') {
            return true;
        }
        if ($this->searchVehicles($keyword)->pluck('id')->contains($vehicle->id)) {
            return true;
        }
        if ($job && $this->searchJobs($keyword)->pluck('id')->contains($job->id)) {
            return true;
        }
        return false;
    }

    protected function documentAllowedForPortal(AutoServiceDocument $document, Request $request): bool
    {
        if ((int) ($document->visible_to_customer ?? 0) !== 1) {
            return false;
        }
        $query = AutoServiceDocument::where('id', $document->id);
        $this->applyBusinessScope($query, 'auto_service_documents');
        if (!$query->exists()) {
            return false;
        }
        $keyword = trim((string) $request->get('q', ''));
        if ($keyword === '') {
            return true;
        }
        if ($document->job_id && $this->searchJobs($keyword)->pluck('id')->contains($document->job_id)) {
            return true;
        }
        if ($document->vehicle_id && $this->searchVehicles($keyword)->pluck('id')->contains($document->vehicle_id)) {
            return true;
        }
        return false;
    }

    protected function logPortalNotification(string $event, $job, $vehicle, string $message): void
    {
        if (!DB::getSchemaBuilder()->hasTable('auto_service_notification_logs')) {
            return;
        }
        DB::table('auto_service_notification_logs')->insert([
            'business_id' => $this->businessId(),
            'job_id' => $job->id ?? null,
            'contact_id' => $job->contact_id ?? $vehicle->contact_id ?? null,
            'channel' => 'in_app',
            'event' => $event,
            'recipient' => 'service_advisor',
            'message' => $message,
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }


    protected function invoiceAllowedForPortal(AutoServiceInvoice $invoice, Request $request): bool
    {
        if (!$request->filled('q')) {
            return true;
        }
        $selectedJob = AutoServiceJob::find($invoice->job_id);
        $allowedInvoices = $this->customerInvoices(null, $selectedJob, $this->customerCanViewCurrentInvoice());
        return $allowedInvoices->pluck('id')->contains($invoice->id);
    }



    protected function approvalAllowedForPortal($approval, $job, $vehicle, Request $request): bool
    {
        $this->applyBusinessScope(AutoServiceApprovalRequest::where('id', $approval->id), 'auto_service_approval_requests');
        $keyword = trim((string) $request->get('q', ''));
        if ($keyword === '') {
            return true;
        }
        $matchingJobs = $this->searchJobs($keyword)->pluck('id');
        if ($matchingJobs->contains($job->id)) {
            return true;
        }
        if ($vehicle) {
            $matchingVehicles = $this->searchVehicles($keyword)->pluck('id');
            return $matchingVehicles->contains($vehicle->id);
        }
        return false;
    }

    protected function portalAppointments($selectedVehicle = null, $selectedJob = null)
    {
        if (!$selectedVehicle || !DB::getSchemaBuilder()->hasTable('auto_service_appointments')) {
            return collect();
        }
        $contactId = $selectedJob->contact_id ?? $selectedVehicle->contact_id ?? null;
        $query = AutoServiceAppointment::query()->where(function ($q) use ($selectedVehicle, $contactId) {
            $q->where('vehicle_id', $selectedVehicle->id);
            if ($contactId) {
                $q->orWhere('contact_id', $contactId);
            }
        });
        $this->applyBusinessScope($query, 'auto_service_appointments');
        return $query->orderByDesc('appointment_at')->orderByDesc('id')->limit(20)->get();
    }

    protected function nextPortalAppointmentNo(): string
    {
        $prefix = 'AS-APT-' . date('ymd') . '-';
        $last = AutoServiceAppointment::where('appointment_no', 'like', $prefix . '%')->orderByDesc('id')->value('appointment_no');
        $next = 1;
        if ($last && preg_match('/(\d+)$/', $last, $m)) {
            $next = ((int) $m[1]) + 1;
        }
        return $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    protected function customerPortalSettings(): array
    {
        if (!DB::getSchemaBuilder()->hasTable('auto_service_settings')) {
            return [];
        }
        return DB::table('auto_service_settings')
            ->where(function ($q) {
                $q->whereNull('business_id')->orWhere('business_id', $this->businessId());
            })
            ->pluck('value', 'key')
            ->map(function ($v) {
                return in_array((string) $v, ['1', 'yes', 'true', 'on'], true);
            })
            ->all();
    }

    protected function customerVehicles($selectedVehicle = null, $selectedJob = null)
    {
        $contactId = $selectedJob->contact_id ?? $selectedVehicle->contact_id ?? null;
        if (!$contactId) {
            return $selectedVehicle ? collect([$selectedVehicle]) : collect();
        }
        return AutoServiceVehicle::where('contact_id', $contactId)->orderBy('registration_no')->get();
    }

    protected function customerCanViewCurrentInvoice(): bool
    {
        if (!DB::getSchemaBuilder()->hasTable('auto_service_settings')) {
            return false;
        }
        $value = DB::table('auto_service_settings')
            ->where('business_id', $this->businessId())
            ->where('key', 'enable_customer_current_invoice_view')
            ->value('value');
        return in_array((string) $value, ['1', 'yes', 'true', 'on'], true);
    }

    protected function customerInvoices($selectedVehicle = null, $selectedJob = null, bool $canViewCurrentInvoice = false)
    {
        if (!DB::getSchemaBuilder()->hasTable('auto_service_invoices')) {
            return collect();
        }

        $contactId = $selectedJob->contact_id ?? $selectedVehicle->contact_id ?? null;
        $vehicleIds = $this->vehicleIdsForCustomer($contactId, $selectedVehicle);
        $jobIds = $this->jobIdsForCustomer($contactId, $vehicleIds, $selectedJob);

        if (!$contactId && $vehicleIds->isEmpty() && $jobIds->isEmpty()) {
            return collect();
        }

        $query = AutoServiceInvoice::with(['lines', 'payments', 'job'])
            ->where(function ($q) use ($contactId, $vehicleIds, $jobIds) {
                if ($contactId && DB::getSchemaBuilder()->hasColumn('auto_service_invoices', 'contact_id')) {
                    $q->orWhere('contact_id', $contactId);
                }
                if ($vehicleIds->isNotEmpty() && DB::getSchemaBuilder()->hasColumn('auto_service_invoices', 'vehicle_id')) {
                    $q->orWhereIn('vehicle_id', $vehicleIds->all());
                }
                if ($jobIds->isNotEmpty()) {
                    $q->orWhereIn('job_id', $jobIds->all());
                }
            });

        $this->applyBusinessScope($query, 'auto_service_invoices');

        if ($selectedJob && !$canViewCurrentInvoice) {
            $query->where(function ($q) use ($selectedJob) {
                $q->whereNull('job_id')->orWhere('job_id', '!=', $selectedJob->id);
            });
        }

        return $query->orderByDesc('invoice_date')->orderByDesc('id')->limit(100)->get();
    }

    protected function currentBill($selectedVehicle = null, $selectedJob = null, bool $canViewCurrentInvoice = false): ?array
    {
        if (!$selectedJob) {
            return null;
        }

        $invoice = null;
        if ($canViewCurrentInvoice && DB::getSchemaBuilder()->hasTable('auto_service_invoices')) {
            $invoice = AutoServiceInvoice::with(['lines', 'payments'])
                ->where('job_id', $selectedJob->id)
                ->orderByDesc('id')
                ->first();
        }

        $lines = collect();
        if ($invoice) {
            $lines = $invoice->lines->map(function ($line) {
                return (object) [
                    'source' => 'invoice',
                    'line_type' => $line->line_type ?? 'service',
                    'description' => $line->description ?? '',
                    'quantity' => (float) ($line->quantity ?? 0),
                    'unit_price' => (float) ($line->unit_price ?? 0),
                    'discount_amount' => (float) ($line->discount_amount ?? 0),
                    'tax_amount' => (float) ($line->tax_amount ?? 0),
                    'line_total' => (float) ($line->line_total ?? 0),
                ];
            });
            return [
                'source' => 'invoice',
                'title' => 'Current Invoice',
                'number' => $invoice->invoice_no,
                'date' => $invoice->invoice_date,
                'status' => $invoice->status,
                'subtotal' => (float) ($invoice->subtotal ?? 0),
                'discount_amount' => (float) ($invoice->discount_amount ?? 0),
                'tax_amount' => (float) ($invoice->tax_amount ?? 0),
                'total_amount' => (float) ($invoice->total_amount ?? 0),
                'paid_amount' => (float) ($invoice->paid_amount ?? 0),
                'balance_amount' => (float) ($invoice->balance_amount ?? 0),
                'lines' => $lines,
            ];
        }

        if (DB::getSchemaBuilder()->hasTable('auto_service_job_lines')) {
            $lines = DB::table('auto_service_job_lines')
                ->where('job_id', $selectedJob->id)
                ->orderBy('id')
                ->get()
                ->map(function ($line) {
                    return (object) [
                        'source' => 'job',
                        'line_type' => $line->line_type ?? 'service',
                        'description' => $line->description ?? '',
                        'quantity' => (float) ($line->quantity ?? 0),
                        'unit_price' => (float) ($line->unit_price ?? 0),
                        'discount_amount' => (float) ($line->discount_amount ?? 0),
                        'tax_amount' => (float) ($line->tax_amount ?? 0),
                        'line_total' => (float) ($line->line_total ?? 0),
                    ];
                });
        }

        return [
            'source' => 'job',
            'title' => 'Current Estimated Bill',
            'number' => $selectedJob->job_no,
            'date' => $selectedJob->job_date,
            'status' => $selectedJob->status,
            'subtotal' => (float) ($selectedJob->subtotal ?? 0),
            'discount_amount' => (float) ($selectedJob->discount_amount ?? 0),
            'tax_amount' => (float) ($selectedJob->tax_amount ?? 0),
            'total_amount' => (float) ($selectedJob->total_amount ?? 0),
            'paid_amount' => (float) ($selectedJob->paid_amount ?? 0),
            'balance_amount' => (float) ($selectedJob->balance_amount ?? 0),
            'lines' => $lines,
        ];
    }

    protected function serviceHistory($selectedVehicle = null, $selectedJob = null)
    {
        if (!$selectedVehicle || !DB::getSchemaBuilder()->hasTable('auto_service_jobs')) {
            return collect();
        }
        $contactId = $selectedJob->contact_id ?? $selectedVehicle->contact_id ?? null;
        $vehicleIds = $this->vehicleIdsForCustomer($contactId, $selectedVehicle);
        $query = AutoServiceJob::query()->where(function ($q) use ($vehicleIds, $contactId) {
            if ($vehicleIds->isNotEmpty()) {
                $q->whereIn('vehicle_id', $vehicleIds->all());
            }
            if ($contactId) {
                $q->orWhere('contact_id', $contactId);
            }
        });
        $this->applyBusinessScope($query, 'auto_service_jobs');
        return $query->orderByDesc('job_date')->orderByDesc('id')->limit(100)->get();
    }


    protected function invoiceLineMoneySelect(): string
    {
        $discount = DB::getSchemaBuilder()->hasColumn('auto_service_invoice_lines', 'discount_amount') ? 'l.discount_amount' : '0';
        $tax = DB::getSchemaBuilder()->hasColumn('auto_service_invoice_lines', 'tax_amount') ? 'l.tax_amount' : '0';
        return "'invoice' as source, i.invoice_date as used_date, i.invoice_no as reference_no, j.job_no, l.product_id, l.description, l.quantity, l.unit_price, COALESCE({$discount},0) as discount_amount, COALESCE({$tax},0) as tax_amount, l.line_total as total_amount, l.line_type";
    }

    protected function movementMoneySelect(): string
    {
        $unit = DB::getSchemaBuilder()->hasColumn('auto_service_part_movements', 'unit_price') ? 'p.unit_price' : 'p.unit_cost';
        $discount = DB::getSchemaBuilder()->hasColumn('auto_service_part_movements', 'discount_amount') ? 'p.discount_amount' : '0';
        return "'movement' as source, p.movement_date as used_date, p.reference_no, j.job_no, p.product_id, p.description, p.quantity, COALESCE({$unit},0) as unit_price, COALESCE({$discount},0) as discount_amount, 0 as tax_amount, COALESCE(p.line_total,0) as total_amount, p.movement_type as line_type";
    }

    protected function jobLineMoneySelect(): string
    {
        $discount = DB::getSchemaBuilder()->hasColumn('auto_service_job_lines', 'discount_amount') ? 'l.discount_amount' : '0';
        $tax = DB::getSchemaBuilder()->hasColumn('auto_service_job_lines', 'tax_amount') ? 'l.tax_amount' : '0';
        return "'job' as source, j.job_date as used_date, j.job_no as reference_no, j.job_no, l.product_id, l.description, l.quantity, l.unit_price, COALESCE({$discount},0) as discount_amount, COALESCE({$tax},0) as tax_amount, l.line_total as total_amount, l.line_type";
    }

    protected function partsHistory($selectedVehicle = null, $selectedJob = null, array $filters = [])
    {
        $rows = collect();
        $contactId = $selectedJob->contact_id ?? $selectedVehicle->contact_id ?? null;
        $vehicleIds = $this->vehicleIdsForCustomer($contactId, $selectedVehicle);
        $jobIds = $this->jobIdsForCustomer($contactId, $vehicleIds, $selectedJob);

        if ($jobIds->isEmpty()) {
            return collect();
        }

        if (DB::getSchemaBuilder()->hasTable('auto_service_invoice_lines')) {
            $query = DB::table('auto_service_invoice_lines as l')
                ->join('auto_service_invoices as i', 'i.id', '=', 'l.invoice_id')
                ->leftJoin('auto_service_jobs as j', 'j.id', '=', 'i.job_id')
                ->whereIn('i.job_id', $jobIds->all())
                ->where(function ($q) {
                    $q->where('l.line_type', 'part')
                      ->orWhere('l.line_type', 'parts')
                      ->orWhere('l.line_type', 'accessory')
                      ->orWhere('l.line_type', 'accessories');
                })
                ->selectRaw($this->invoiceLineMoneySelect())
                ->orderByDesc('i.invoice_date')
                ->orderByDesc('l.id');
            $this->applyPartsFilters($query, $filters, 'i.invoice_date', 'l.description');
            $rows = $rows->merge($query->limit(250)->get());
        }

        if (DB::getSchemaBuilder()->hasTable('auto_service_part_movements')) {
            $query = DB::table('auto_service_part_movements as p')
                ->leftJoin('auto_service_jobs as j', 'j.id', '=', 'p.job_id')
                ->whereIn('p.job_id', $jobIds->all())
                ->selectRaw($this->movementMoneySelect())
                ->orderByDesc('p.movement_date')
                ->orderByDesc('p.id');
            $this->applyPartsFilters($query, $filters, 'p.movement_date', 'p.description');
            $rows = $rows->merge($query->limit(250)->get());
        }

        if (DB::getSchemaBuilder()->hasTable('auto_service_job_lines')) {
            $query = DB::table('auto_service_job_lines as l')
                ->leftJoin('auto_service_jobs as j', 'j.id', '=', 'l.job_id')
                ->whereIn('l.job_id', $jobIds->all())
                ->where(function ($q) {
                    $q->where('l.line_type', 'part')
                      ->orWhere('l.line_type', 'parts')
                      ->orWhere('l.line_type', 'accessory')
                      ->orWhere('l.line_type', 'accessories');
                })
                ->selectRaw($this->jobLineMoneySelect())
                ->orderByDesc('j.job_date')
                ->orderByDesc('l.id');
            $this->applyPartsFilters($query, $filters, 'j.job_date', 'l.description');
            $rows = $rows->merge($query->limit(250)->get());
        }

        return $rows->sortByDesc(function ($row) {
            return (string) ($row->used_date ?? '');
        })->values()->take(300);
    }

    protected function partsSummary($partsHistory): array
    {
        return [
            'qty' => (float) $partsHistory->sum('quantity'),
            'subtotal' => (float) $partsHistory->sum(function ($row) {
                return ((float) ($row->quantity ?? 0)) * ((float) ($row->unit_price ?? 0));
            }),
            'discount' => (float) $partsHistory->sum('discount_amount'),
            'total' => (float) $partsHistory->sum('total_amount'),
        ];
    }

    protected function partFilters(Request $request): array
    {
        return [
            'part_q' => trim((string) $request->get('part_q', '')),
            'date_from' => trim((string) $request->get('date_from', '')),
            'date_to' => trim((string) $request->get('date_to', '')),
            'job_no' => trim((string) $request->get('job_no', '')),
        ];
    }

    protected function applyPartsFilters($query, array $filters, string $dateColumn, string $descriptionColumn): void
    {
        if (!empty($filters['date_from'])) {
            $query->whereDate($dateColumn, '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->whereDate($dateColumn, '<=', $filters['date_to']);
        }
        if (!empty($filters['part_q'])) {
            $like = '%' . $filters['part_q'] . '%';
            $query->where(function ($q) use ($descriptionColumn, $like) {
                $q->where($descriptionColumn, 'like', $like)
                  ->orWhere('j.job_no', 'like', $like);
            });
        }
        if (!empty($filters['job_no'])) {
            $query->where('j.job_no', 'like', '%' . $filters['job_no'] . '%');
        }
    }

    protected function vehicleIdsForCustomer($contactId = null, $selectedVehicle = null)
    {
        if ($contactId && DB::getSchemaBuilder()->hasTable('auto_service_vehicles')) {
            return AutoServiceVehicle::where('contact_id', $contactId)->pluck('id');
        }
        return $selectedVehicle ? collect([$selectedVehicle->id]) : collect();
    }

    protected function jobIdsForCustomer($contactId = null, $vehicleIds = null, $selectedJob = null)
    {
        $vehicleIds = $vehicleIds ?: collect();
        if (!DB::getSchemaBuilder()->hasTable('auto_service_jobs')) {
            return $selectedJob ? collect([$selectedJob->id]) : collect();
        }
        $query = AutoServiceJob::query()->where(function ($q) use ($contactId, $vehicleIds, $selectedJob) {
            if ($contactId) {
                $q->orWhere('contact_id', $contactId);
            }
            if ($vehicleIds->isNotEmpty()) {
                $q->orWhereIn('vehicle_id', $vehicleIds->all());
            }
            if ($selectedJob) {
                $q->orWhere('id', $selectedJob->id);
            }
        });
        $this->applyBusinessScope($query, 'auto_service_jobs');
        return $query->pluck('id');
    }

    protected function applyBusinessScope($query, string $table): void
    {
        $businessId = $this->businessId();
        if ($businessId && DB::getSchemaBuilder()->hasColumn($table, 'business_id')) {
            $query->where($table . '.business_id', $businessId);
        }
    }

    protected function searchJobs(string $keyword)
    {
        $like = '%' . $keyword . '%';
        $query = AutoServiceJob::query()
            ->where(function ($q) use ($like) {
                $q->where('job_no', 'like', $like);

                if (DB::getSchemaBuilder()->hasTable('auto_service_vehicles')) {
                    $q->orWhereIn('vehicle_id', function ($sq) use ($like) {
                        $sq->select('id')->from('auto_service_vehicles')
                            ->where('registration_no', 'like', $like)
                            ->orWhere('vin', 'like', $like)
                            ->orWhere('chassis_no', 'like', $like)
                            ->orWhere('engine_no', 'like', $like);
                    });
                }

                if (DB::getSchemaBuilder()->hasTable('contacts')) {
                    $q->orWhereIn('contact_id', function ($sq) use ($like) {
                        $sq->select('id')->from('contacts')
                            ->where('mobile', 'like', $like)
                            ->orWhere('alternate_number', 'like', $like)
                            ->orWhere('landline', 'like', $like)
                            ->orWhere('name', 'like', $like)
                            ->orWhere('supplier_business_name', 'like', $like);
                    });
                }
            });
        $this->applyBusinessScope($query, 'auto_service_jobs');
        return $query->orderByDesc('job_date')->orderByDesc('id')->limit(20)->get();
    }

    protected function searchVehicles(string $keyword)
    {
        $like = '%' . $keyword . '%';
        $query = AutoServiceVehicle::where(function ($q) use ($like) {
            $q->where('registration_no', 'like', $like)
                ->orWhere('vin', 'like', $like)
                ->orWhere('chassis_no', 'like', $like)
                ->orWhere('engine_no', 'like', $like);

            if (DB::getSchemaBuilder()->hasTable('contacts')) {
                $q->orWhereIn('contact_id', function ($sq) use ($like) {
                    $sq->select('id')->from('contacts')
                        ->where('mobile', 'like', $like)
                        ->orWhere('alternate_number', 'like', $like)
                        ->orWhere('landline', 'like', $like)
                        ->orWhere('name', 'like', $like)
                        ->orWhere('supplier_business_name', 'like', $like);
                });
            }
        });
        $this->applyBusinessScope($query, 'auto_service_vehicles');
        return $query->orderByDesc('id')->limit(20)->get();
    }


    protected function portalAlerts($selectedVehicle = null, $selectedJob = null)
    {
        if (!$selectedVehicle || !DB::getSchemaBuilder()->hasTable('auto_service_customer_portal_alerts')) {
            return collect();
        }
        $query = DB::table('auto_service_customer_portal_alerts')
            ->where(function ($q) use ($selectedVehicle, $selectedJob) {
                $q->where('vehicle_id', $selectedVehicle->id);
                if ($selectedJob) {
                    $q->orWhere('job_id', $selectedJob->id);
                }
            });
        $this->applyBusinessScope($query, 'auto_service_customer_portal_alerts');
        return $query->orderByRaw("CASE WHEN status = 'new' THEN 0 WHEN status = 'sent' THEN 1 ELSE 2 END")
            ->orderByDesc('created_at')
            ->limit(25)
            ->get();
    }

    protected function portalCommunicationLog($selectedVehicle = null, $selectedJob = null)
    {
        if (!$selectedVehicle || !DB::getSchemaBuilder()->hasTable('auto_service_notification_logs')) {
            return collect();
        }
        $contactId = $selectedJob->contact_id ?? $selectedVehicle->contact_id ?? null;
        $query = DB::table('auto_service_notification_logs')
            ->where(function ($q) use ($selectedJob, $contactId) {
                if ($selectedJob) {
                    $q->orWhere('job_id', $selectedJob->id);
                }
                if ($contactId && DB::getSchemaBuilder()->hasColumn('auto_service_notification_logs', 'contact_id')) {
                    $q->orWhere('contact_id', $contactId);
                }
            });
        $this->applyBusinessScope($query, 'auto_service_notification_logs');
        return $query->orderByDesc('created_at')->limit(50)->get();
    }

    protected function createPortalAlert($job, $vehicle, string $eventType, string $title, string $message, string $priority = 'normal'): void
    {
        if (!DB::getSchemaBuilder()->hasTable('auto_service_customer_portal_alerts')) {
            return;
        }
        DB::table('auto_service_customer_portal_alerts')->insert([
            'business_id' => $this->businessId(),
            'location_id' => $this->locationId(),
            'job_id' => $job->id ?? null,
            'vehicle_id' => $vehicle->id ?? null,
            'contact_id' => $job->contact_id ?? $vehicle->contact_id ?? null,
            'event_type' => $eventType,
            'title' => $title,
            'message' => $message,
            'priority' => $priority,
            'status' => 'new',
            'visible_to_customer' => 1,
            'created_by' => auth()->id(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }


    public function downloadServiceSummary(Request $request)
    {
        $context = $this->resolveCustomerPortalContext($request);
        if (!$context['vehicle']) {
            abort(404, 'No customer service record found for this lookup.');
        }

        $portalSettings = $this->customerPortalSettings();
        $canViewCurrentInvoice = !empty($portalSettings['allow_customer_current_invoice_view']) || !empty($portalSettings['enable_customer_current_invoice_view']);
        $currentBill = $this->currentBill($context['vehicle'], $context['job'], $canViewCurrentInvoice);
        $serviceHistory = $this->serviceHistory($context['vehicle'], $context['job']);
        $partsHistory = $this->partsHistory($context['vehicle'], $context['job'], $this->partFilters($request));
        $partsSummary = $this->partsSummary($partsHistory);

        return view('autoservice::customer_portal.print.service_summary', [
            'keyword' => $context['keyword'],
            'selectedVehicle' => $context['vehicle'],
            'selectedJob' => $context['job'],
            'currentBill' => $currentBill,
            'serviceHistory' => $serviceHistory,
            'partsHistory' => $partsHistory,
            'partsSummary' => $partsSummary,
            'generatedAt' => now(),
        ]);
    }

    public function exportPartsHistoryCsv(Request $request)
    {
        $context = $this->resolveCustomerPortalContext($request);
        if (!$context['vehicle']) {
            abort(404, 'No customer service record found for this lookup.');
        }

        $rows = $this->partsHistory($context['vehicle'], $context['job'], $this->partFilters($request));
        $fileName = 'auto-service-parts-history-' . date('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Date', 'Job No', 'Reference', 'Type', 'Part / Accessory', 'Qty', 'Unit Price', 'Discount', 'Tax', 'Total Amount']);
            foreach ($rows as $row) {
                fputcsv($out, [
                    $row->used_date ?? '',
                    $row->job_no ?? '',
                    $row->reference_no ?? '',
                    ucwords(str_replace('_', ' ', (string) ($row->line_type ?? ''))),
                    $row->description ?? '',
                    number_format((float) ($row->quantity ?? 0), 2, '.', ''),
                    number_format((float) ($row->unit_price ?? 0), 2, '.', ''),
                    number_format((float) ($row->discount_amount ?? 0), 2, '.', ''),
                    number_format((float) ($row->tax_amount ?? 0), 2, '.', ''),
                    number_format((float) ($row->total_amount ?? 0), 2, '.', ''),
                ]);
            }
            fclose($out);
        }, $fileName, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function paymentHistory(Request $request)
    {
        $context = $this->resolveCustomerPortalContext($request);
        if (!$context['vehicle']) {
            abort(404, 'No customer service record found for this lookup.');
        }

        $invoices = $this->customerInvoices($context['vehicle'], $context['job'], true);
        $invoiceIds = $invoices->pluck('id')->all();
        $payments = collect();
        if (!empty($invoiceIds) && DB::getSchemaBuilder()->hasTable('auto_service_payments')) {
            $payments = DB::table('auto_service_payments as p')
                ->leftJoin('auto_service_invoices as i', 'i.id', '=', 'p.invoice_id')
                ->leftJoin('auto_service_jobs as j', 'j.id', '=', 'i.job_id')
                ->whereIn('p.invoice_id', $invoiceIds)
                ->select('p.*', 'i.invoice_no', 'j.job_no')
                ->orderByDesc('p.payment_date')
                ->orderByDesc('p.id')
                ->limit(200)
                ->get();
        }

        return view('autoservice::customer_portal.payment_history', [
            'keyword' => $context['keyword'],
            'selectedVehicle' => $context['vehicle'],
            'selectedJob' => $context['job'],
            'payments' => $payments,
            'invoices' => $invoices,
        ]);
    }

    protected function resolveCustomerPortalContext(Request $request): array
    {
        $keyword = trim((string) $request->get('q', $request->get('registration_no', '')));
        $jobs = collect();
        $vehicles = collect();
        $selectedJob = null;
        $selectedVehicle = null;

        if ($keyword !== '') {
            $jobs = $this->searchJobs($keyword);
            if ($jobs->isEmpty()) {
                $vehicles = $this->searchVehicles($keyword);
                if ($vehicles->isNotEmpty()) {
                    $jobs = AutoServiceJob::whereIn('vehicle_id', $vehicles->pluck('id')->all())
                        ->orderByDesc('job_date')
                        ->orderByDesc('id')
                        ->limit(20)
                        ->get();
                }
            }
            if ($jobs->isNotEmpty()) {
                $selectedJob = $jobs->first();
                $selectedVehicle = AutoServiceVehicle::find($selectedJob->vehicle_id);
            } elseif ($vehicles->isNotEmpty()) {
                $selectedVehicle = $vehicles->first();
            }
        }

        return ['keyword' => $keyword, 'job' => $selectedJob, 'vehicle' => $selectedVehicle];
    }

    protected function statusSteps(): array
    {
        return [
            'received' => 'Vehicle Received',
            'estimated' => 'Estimate Prepared',
            'approved' => 'Approved',
            'waiting_parts' => 'Waiting Parts',
            'in_progress' => 'Work In Progress',
            'quality_check' => 'Quality Check',
            'ready' => 'Ready for Delivery',
            'delivered' => 'Delivered',
            'cancelled' => 'Cancelled',
        ];
    }
}
