<?php

namespace Modules\AutoService\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ManagementKpiController extends AutoServiceBaseController
{
    public function index(Request $request)
    {
        $businessId = $this->businessId();
        $locationId = $this->locationId();
        $from = $request->get('from') ?: date('Y-m-01');
        $to = $request->get('to') ?: date('Y-m-d');
        $status = $request->get('status');
        $search = trim((string) $request->get('search'));

        $jobsBase = DB::table('auto_service_jobs as j')
            ->leftJoin('auto_service_vehicles as v', 'v.id', '=', 'j.vehicle_id')
            ->leftJoin('contacts as c', 'c.id', '=', 'j.contact_id')
            ->when($businessId, fn($q) => $q->where('j.business_id', $businessId))
            ->when($locationId, fn($q) => $q->where(function($qq) use ($locationId) {
                $qq->whereNull('j.location_id')->orWhere('j.location_id', $locationId);
            }))
            ->when($from, fn($q) => $q->whereDate('j.created_at', '>=', $from))
            ->when($to, fn($q) => $q->whereDate('j.created_at', '<=', $to));

        $stats = [
            'total_jobs' => (clone $jobsBase)->count(),
            'open_jobs' => (clone $jobsBase)->whereNotIn('j.status', ['completed','delivered','cancelled'])->count(),
            'completed_jobs' => (clone $jobsBase)->whereIn('j.status', ['completed','delivered'])->count(),
            'repeat_repairs' => DB::table('auto_service_repeat_repairs')->when($businessId, fn($q) => $q->where('business_id', $businessId))->whereBetween('created_at', [$from . ' 00:00:00', $to . ' 23:59:59'])->count(),
            'revenue' => DB::table('auto_service_invoices')->when($businessId, fn($q) => $q->where('business_id', $businessId))->whereBetween('created_at', [$from . ' 00:00:00', $to . ' 23:59:59'])->sum('total_amount'),
            'pending_qc' => DB::table('auto_service_jobs')->when($businessId, fn($q) => $q->where('business_id', $businessId))->whereIn('status', ['ready_for_qc','qc_pending'])->count(),
        ];

        $jobs = (clone $jobsBase)
            ->select('j.*', 'v.registration_no', 'v.make', 'v.model', 'c.name as customer_name', 'c.mobile as customer_mobile')
            ->when($status, fn($q) => $q->where('j.status', $status))
            ->when($search, function($q) use ($search) {
                $q->where(function($qq) use ($search) {
                    $qq->where('j.job_no', 'like', "%{$search}%")
                       ->orWhere('v.registration_no', 'like', "%{$search}%")
                       ->orWhere('c.name', 'like', "%{$search}%")
                       ->orWhere('c.mobile', 'like', "%{$search}%");
                });
            })
            ->orderByRaw("case when j.status in ('completed','delivered','cancelled') then 1 else 0 end")
            ->orderBy('j.created_at')
            ->paginate(25)
            ->appends($request->query());

        $technicians = DB::table('auto_service_job_mechanics as jm')
            ->leftJoin('auto_service_mechanics as m', 'm.id', '=', 'jm.mechanic_id')
            ->leftJoin('auto_service_jobs as j', 'j.id', '=', 'jm.job_id')
            ->select('m.id', 'm.name', DB::raw('count(distinct jm.job_id) as assigned_jobs'), DB::raw("sum(case when j.status in ('completed','delivered') then 1 else 0 end) as completed_jobs"))
            ->when($businessId, fn($q) => $q->where('j.business_id', $businessId))
            ->whereBetween('j.created_at', [$from . ' 00:00:00', $to . ' 23:59:59'])
            ->groupBy('m.id', 'm.name')
            ->orderByDesc('completed_jobs')
            ->limit(15)
            ->get();

        $bayUtilisation = DB::table('auto_service_bay_allocations as ba')
            ->leftJoin('auto_service_bays as b', 'b.id', '=', 'ba.bay_id')
            ->select('b.name', DB::raw('count(ba.id) as allocations'), DB::raw('sum(case when ba.released_at is null then 1 else 0 end) as currently_occupied'))
            ->when($businessId, fn($q) => $q->where('ba.business_id', $businessId))
            ->whereBetween('ba.created_at', [$from . ' 00:00:00', $to . ' 23:59:59'])
            ->groupBy('b.name')
            ->orderByDesc('allocations')
            ->get();

        return view('autoservice::management_kpi.index', compact('stats', 'jobs', 'technicians', 'bayUtilisation', 'from', 'to', 'status', 'search'));
    }

    public function repeatRepairStore(Request $request)
    {
        $data = $request->validate([
            'job_id' => 'required|integer',
            'reason' => 'required|string|max:255',
            'corrective_action' => 'nullable|string',
            'severity' => 'nullable|string|in:low,medium,high,critical',
        ]);

        $job = DB::table('auto_service_jobs')->where('id', $data['job_id'])
            ->when($this->businessId(), fn($q) => $q->where('business_id', $this->businessId()))
            ->first();

        if (!$job) {
            return back()->withErrors(['job_id' => 'Job not found for this business.']);
        }

        DB::table('auto_service_repeat_repairs')->insert([
            'business_id' => $job->business_id,
            'location_id' => $job->location_id ?? $this->locationId(),
            'job_id' => $job->id,
            'vehicle_id' => $job->vehicle_id,
            'contact_id' => $job->contact_id ?? null,
            'reason' => $data['reason'],
            'corrective_action' => $data['corrective_action'] ?? null,
            'severity' => $data['severity'] ?? 'medium',
            'status' => 'open',
            'created_by' => auth()->id(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('auto_service_timeline')->insert([
            'business_id' => $job->business_id,
            'vehicle_id' => $job->vehicle_id,
            'job_id' => $job->id,
            'event_type' => 'repeat_repair',
            'title' => 'Repeat Repair Recorded',
            'description' => $data['reason'],
            'created_by' => auth()->id(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return back()->with('status', 'Repeat repair entry recorded.');
    }
}
