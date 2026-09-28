<?php

namespace Modules\AutoService\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MaintenancePlannerController extends AutoServiceBaseController
{
    public function index(Request $request)
    {
        $businessId = $this->businessId();
        $locationId = $this->locationId();
        $search = trim((string) $request->get('search'));
        $status = $request->get('status');
        $from = $request->get('from');
        $to = $request->get('to');

        $plans = DB::table('auto_service_maintenance_plans as mp')
            ->leftJoin('auto_service_vehicles as v', 'v.id', '=', 'mp.vehicle_id')
            ->leftJoin('contacts as c', 'c.id', '=', 'mp.contact_id')
            ->select('mp.*', 'v.registration_no', 'v.make', 'v.model', 'c.name as customer_name', 'c.mobile as customer_mobile')
            ->when($businessId, fn($q) => $q->where('mp.business_id', $businessId))
            ->when($locationId, fn($q) => $q->where(function($qq) use ($locationId) {
                $qq->whereNull('mp.location_id')->orWhere('mp.location_id', $locationId);
            }))
            ->when($status, fn($q) => $q->where('mp.status', $status))
            ->when($from, fn($q) => $q->whereDate('mp.next_service_date', '>=', $from))
            ->when($to, fn($q) => $q->whereDate('mp.next_service_date', '<=', $to))
            ->when($search, function($q) use ($search) {
                $q->where(function($qq) use ($search) {
                    $qq->where('mp.plan_no', 'like', "%{$search}%")
                       ->orWhere('v.registration_no', 'like', "%{$search}%")
                       ->orWhere('v.make', 'like', "%{$search}%")
                       ->orWhere('v.model', 'like', "%{$search}%")
                       ->orWhere('c.name', 'like', "%{$search}%")
                       ->orWhere('c.mobile', 'like', "%{$search}%");
                });
            })
            ->orderBy('mp.next_service_date')
            ->orderByDesc('mp.id')
            ->paginate(25)
            ->appends($request->query());

        $stats = [
            'active' => $this->countPlans('active'),
            'due_soon' => DB::table('auto_service_maintenance_plans')
                ->when($businessId, fn($q) => $q->where('business_id', $businessId))
                ->where('status', 'active')
                ->whereBetween('next_service_date', [date('Y-m-d'), date('Y-m-d', strtotime('+30 days'))])
                ->count(),
            'overdue' => DB::table('auto_service_maintenance_plans')
                ->when($businessId, fn($q) => $q->where('business_id', $businessId))
                ->where('status', 'active')
                ->whereDate('next_service_date', '<', date('Y-m-d'))
                ->count(),
            'completed' => $this->countPlans('completed'),
        ];

        return view('autoservice::maintenance_planner.index', compact('plans', 'stats', 'search', 'status', 'from', 'to'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'vehicle_id' => 'required|integer',
            'job_id' => 'nullable|integer',
            'plan_type' => 'nullable|string|max:60',
            'current_meter' => 'nullable|numeric|min:0',
            'next_service_date' => 'required|date',
            'next_service_meter' => 'nullable|numeric|min:0',
            'interval_days' => 'nullable|integer|min:0|max:3650',
            'interval_meter' => 'nullable|numeric|min:0',
            'service_note' => 'nullable|string',
            'recommended_parts' => 'nullable|string',
            'customer_visible' => 'nullable|boolean',
        ]);

        $vehicle = DB::table('auto_service_vehicles')->where('id', $data['vehicle_id'])
            ->when($this->businessId(), fn($q) => $q->where('business_id', $this->businessId()))
            ->first();

        if (!$vehicle) {
            return back()->withErrors(['vehicle_id' => 'Vehicle not found for this business.']);
        }

        $planNo = 'MP-' . date('Ymd') . '-' . str_pad((string) (DB::table('auto_service_maintenance_plans')->whereDate('created_at', date('Y-m-d'))->count() + 1), 4, '0', STR_PAD_LEFT);

        $id = DB::table('auto_service_maintenance_plans')->insertGetId([
            'business_id' => $vehicle->business_id,
            'location_id' => $vehicle->location_id ?? $this->locationId(),
            'contact_id' => $vehicle->contact_id ?? null,
            'vehicle_id' => $vehicle->id,
            'job_id' => $data['job_id'] ?? null,
            'plan_no' => $planNo,
            'plan_type' => $data['plan_type'] ?? 'periodic_service',
            'current_meter' => $data['current_meter'] ?? null,
            'next_service_date' => $data['next_service_date'],
            'next_service_meter' => $data['next_service_meter'] ?? null,
            'interval_days' => $data['interval_days'] ?? null,
            'interval_meter' => $data['interval_meter'] ?? null,
            'service_note' => $data['service_note'] ?? null,
            'recommended_parts' => $data['recommended_parts'] ?? null,
            'customer_visible' => (int) ($data['customer_visible'] ?? 1),
            'status' => 'active',
            'created_by' => auth()->id(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->timeline($vehicle->id, $data['job_id'] ?? null, 'maintenance_plan', 'Maintenance Plan Created', $planNo . ' created for next service on ' . $data['next_service_date']);

        return back()->with('status', 'Maintenance plan created successfully.');
    }

    public function updateStatus(Request $request, int $plan)
    {
        $data = $request->validate([
            'status' => 'required|string|in:active,paused,completed,cancelled',
            'completion_note' => 'nullable|string',
        ]);

        $row = DB::table('auto_service_maintenance_plans')->where('id', $plan)
            ->when($this->businessId(), fn($q) => $q->where('business_id', $this->businessId()))
            ->first();
        if (!$row) {
            return back()->withErrors(['plan' => 'Maintenance plan not found for this business.']);
        }

        DB::table('auto_service_maintenance_plans')->where('id', $plan)->update([
            'status' => $data['status'],
            'completion_note' => $data['completion_note'] ?? $row->completion_note,
            'completed_by' => $data['status'] === 'completed' ? auth()->id() : $row->completed_by,
            'completed_at' => $data['status'] === 'completed' ? now() : $row->completed_at,
            'updated_at' => now(),
        ]);

        $this->timeline($row->vehicle_id, $row->job_id, 'maintenance_status', 'Maintenance Plan Updated', $row->plan_no . ' changed to ' . $data['status']);

        return back()->with('status', 'Maintenance plan updated.');
    }

    public function vehicleHealth(Request $request)
    {
        $businessId = $this->businessId();
        $search = trim((string) $request->get('search'));

        $vehicles = DB::table('auto_service_vehicles as v')
            ->leftJoin('contacts as c', 'c.id', '=', 'v.contact_id')
            ->leftJoin(DB::raw('(select vehicle_id, max(job_date) as last_service_date, max(id) as last_job_id from auto_service_jobs group by vehicle_id) lj'), 'lj.vehicle_id', '=', 'v.id')
            ->select('v.*', 'c.name as customer_name', 'c.mobile as customer_mobile', 'lj.last_service_date', 'lj.last_job_id')
            ->when($businessId, fn($q) => $q->where('v.business_id', $businessId))
            ->when($search, function($q) use ($search) {
                $q->where(function($qq) use ($search) {
                    $qq->where('v.registration_no', 'like', "%{$search}%")
                       ->orWhere('v.make', 'like', "%{$search}%")
                       ->orWhere('v.model', 'like', "%{$search}%")
                       ->orWhere('c.name', 'like', "%{$search}%")
                       ->orWhere('c.mobile', 'like', "%{$search}%");
                });
            })
            ->orderBy('v.registration_no')
            ->paginate(25)
            ->appends($request->query());

        return view('autoservice::maintenance_planner.vehicle_health', compact('vehicles', 'search'));
    }

    protected function countPlans(string $status): int
    {
        return DB::table('auto_service_maintenance_plans')
            ->when($this->businessId(), fn($q) => $q->where('business_id', $this->businessId()))
            ->where('status', $status)
            ->count();
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
