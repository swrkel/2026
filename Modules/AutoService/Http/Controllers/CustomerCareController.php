<?php

namespace Modules\AutoService\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomerCareController extends AutoServiceBaseController
{
    public function index(Request $request)
    {
        $businessId = $this->businessId();
        $locationId = $this->locationId();

        $feedback = DB::table('auto_service_feedback')
            ->when($businessId, fn($q) => $q->where('business_id', $businessId))
            ->when($locationId, fn($q) => $q->where(function($qq) use ($locationId) {
                $qq->whereNull('location_id')->orWhere('location_id', $locationId);
            }))
            ->orderByDesc('submitted_at')
            ->orderByDesc('id')
            ->limit(20)
            ->get();

        $reminders = DB::table('auto_service_reminders as r')
            ->leftJoin('auto_service_vehicles as v', 'v.id', '=', 'r.vehicle_id')
            ->select('r.*', 'v.registration_no', 'v.make', 'v.model')
            ->when($businessId, fn($q) => $q->where('r.business_id', $businessId))
            ->where('r.status', 'pending')
            ->orderBy('r.send_on')
            ->limit(25)
            ->get();

        $warrantyClaims = DB::table('auto_service_warranty_claims as wc')
            ->leftJoin('auto_service_jobs as j', 'j.id', '=', 'wc.job_id')
            ->leftJoin('auto_service_vehicles as v', 'v.id', '=', 'wc.vehicle_id')
            ->select('wc.*', 'j.job_no', 'v.registration_no')
            ->when($businessId, fn($q) => $q->where('wc.business_id', $businessId))
            ->when($locationId, fn($q) => $q->where(function($qq) use ($locationId) {
                $qq->whereNull('wc.location_id')->orWhere('wc.location_id', $locationId);
            }))
            ->orderByDesc('wc.id')
            ->limit(25)
            ->get();

        $stats = [
            'feedback_count' => DB::table('auto_service_feedback')->when($businessId, fn($q) => $q->where('business_id', $businessId))->count(),
            'avg_service_rating' => round((float) DB::table('auto_service_feedback')->when($businessId, fn($q) => $q->where('business_id', $businessId))->avg('service_rating'), 2),
            'pending_reminders' => DB::table('auto_service_reminders')->when($businessId, fn($q) => $q->where('business_id', $businessId))->where('status', 'pending')->count(),
            'open_warranty_claims' => DB::table('auto_service_warranty_claims')->when($businessId, fn($q) => $q->where('business_id', $businessId))->whereIn('status', ['open','in_review','approved'])->count(),
        ];

        return view('autoservice::customer_care.index', compact('feedback', 'reminders', 'warrantyClaims', 'stats'));
    }

    public function createWarrantyClaim(Request $request)
    {
        $data = $request->validate([
            'job_id' => 'required|integer',
            'claim_type' => 'nullable|string|max:100',
            'customer_complaint' => 'nullable|string',
            'internal_note' => 'nullable|string',
        ]);

        $job = DB::table('auto_service_jobs')->where('id', $data['job_id'])
            ->when($this->businessId(), fn($q) => $q->where('business_id', $this->businessId()))
            ->first();

        if (!$job) {
            return back()->withErrors(['job_id' => 'Job card not found for this business.']);
        }

        $exists = DB::table('auto_service_warranty_claims')
            ->where('job_id', $job->id)
            ->whereIn('status', ['open','in_review','approved'])
            ->exists();

        if ($exists) {
            return back()->with('status', 'An open warranty claim already exists for this job.');
        }

        $claimNo = 'WC-' . date('Ymd') . '-' . str_pad((string) ((int) DB::table('auto_service_warranty_claims')->whereDate('created_at', date('Y-m-d'))->count() + 1), 4, '0', STR_PAD_LEFT);

        $id = DB::table('auto_service_warranty_claims')->insertGetId([
            'business_id' => $job->business_id,
            'location_id' => $job->location_id,
            'contact_id' => $job->contact_id,
            'vehicle_id' => $job->vehicle_id,
            'job_id' => $job->id,
            'claim_no' => $claimNo,
            'claim_type' => $data['claim_type'] ?? 'service_warranty',
            'status' => 'open',
            'customer_complaint' => $data['customer_complaint'] ?? null,
            'internal_note' => $data['internal_note'] ?? null,
            'created_by' => auth()->id(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->timeline($job->vehicle_id, $job->id, 'warranty_claim', 'Warranty Claim Opened', $claimNo . ' has been opened.');

        return back()->with('status', 'Warranty claim created successfully.');
    }

    public function updateWarrantyStatus(Request $request, int $claim)
    {
        $data = $request->validate([
            'status' => 'required|string|in:open,in_review,approved,rejected,completed,cancelled',
            'internal_note' => 'nullable|string',
        ]);

        $row = DB::table('auto_service_warranty_claims')->where('id', $claim)
            ->when($this->businessId(), fn($q) => $q->where('business_id', $this->businessId()))
            ->first();

        if (!$row) {
            return back()->withErrors(['claim' => 'Warranty claim not found for this business.']);
        }

        DB::table('auto_service_warranty_claims')->where('id', $claim)->update([
            'status' => $data['status'],
            'internal_note' => $data['internal_note'] ?? $row->internal_note,
            'resolved_by' => in_array($data['status'], ['completed','rejected','cancelled'], true) ? auth()->id() : $row->resolved_by,
            'resolved_at' => in_array($data['status'], ['completed','rejected','cancelled'], true) ? now() : $row->resolved_at,
            'updated_at' => now(),
        ]);

        $this->timeline($row->vehicle_id, $row->job_id, 'warranty_status', 'Warranty Claim Updated', 'Status changed to ' . $data['status']);

        return back()->with('status', 'Warranty claim updated.');
    }

    public function generateReminder(Request $request)
    {
        $data = $request->validate([
            'vehicle_id' => 'required|integer',
            'job_id' => 'nullable|integer',
            'due_date' => 'required|date',
            'days_before' => 'nullable|integer|min:0|max:365',
            'channel' => 'nullable|string|max:30',
            'mobile' => 'nullable|string|max:50',
            'message' => 'nullable|string',
        ]);

        $vehicle = DB::table('auto_service_vehicles')->where('id', $data['vehicle_id'])
            ->when($this->businessId(), fn($q) => $q->where('business_id', $this->businessId()))
            ->first();

        if (!$vehicle) {
            return back()->withErrors(['vehicle_id' => 'Vehicle not found for this business.']);
        }

        $daysBefore = (int) ($data['days_before'] ?? 7);
        $sendOn = date('Y-m-d', strtotime($data['due_date'] . ' -' . $daysBefore . ' days'));
        $message = $data['message'] ?: 'Service reminder for vehicle ' . $vehicle->registration_no . ' due on ' . $data['due_date'];

        DB::table('auto_service_reminders')->insert([
            'business_id' => $vehicle->business_id,
            'vehicle_id' => $vehicle->id,
            'contact_id' => $vehicle->contact_id,
            'job_id' => $data['job_id'] ?? null,
            'due_date' => $data['due_date'],
            'days_before' => $daysBefore,
            'send_on' => $sendOn,
            'channel' => $data['channel'] ?? 'sms',
            'mobile' => $data['mobile'] ?? null,
            'message' => $message,
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->timeline($vehicle->id, $data['job_id'] ?? null, 'service_reminder', 'Service Reminder Created', $message);

        return back()->with('status', 'Service reminder created.');
    }

    public function closeFeedback(Request $request, int $feedback)
    {
        DB::table('auto_service_feedback')->where('id', $feedback)
            ->when($this->businessId(), fn($q) => $q->where('business_id', $this->businessId()))
            ->update([
                'review_status' => 'closed',
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now(),
                'review_note' => $request->input('review_note'),
                'updated_at' => now(),
            ]);

        return back()->with('status', 'Feedback closed.');
    }

    protected function timeline($vehicleId, $jobId, string $type, string $title, string $description): void
    {
        if (!$vehicleId || !DB::getSchemaBuilder()->hasTable('auto_service_timeline')) {
            return;
        }

        DB::table('auto_service_timeline')->insert([
            'business_id' => $this->businessId(),
            'location_id' => $this->locationId(),
            'vehicle_id' => $vehicleId,
            'job_id' => $jobId,
            'event_type' => $type,
            'title' => $title,
            'description' => $description,
            'event_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
