<?php

namespace Modules\AutoService\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

class WorkshopPlanningController extends Controller
{
    protected function businessId()
    {
        return session('business.id') ?? request()->session()->get('user.business_id') ?? auth()->user()->business_id ?? null;
    }

    protected function locationId(Request $request)
    {
        return $request->input('business_location_id') ?: session('business_location_id') ?: null;
    }

    public function index(Request $request)
    {
        $businessId = $this->businessId();
        $locationId = $this->locationId($request);
        $date = $request->input('date', date('Y-m-d'));

        $jobs = DB::table('autoservice_jobs')
            ->where('business_id', $businessId)
            ->when($locationId, fn($q) => $q->where('business_location_id', $locationId))
            ->whereIn('status', ['open','received','inspection','estimate','approved','repair','in_progress','hold','waiting_parts','qc','ready_for_delivery'])
            ->orderByDesc('id')
            ->limit(80)
            ->get();

        $technicians = DB::table('autoservice_mechanics')
            ->where('business_id', $businessId)
            ->when($locationId, fn($q) => $q->where('business_location_id', $locationId))
            ->where(function($q){ $q->whereNull('is_active')->orWhere('is_active', 1); })
            ->orderBy('name')
            ->get();

        $bays = DB::table('autoservice_bays')
            ->where('business_id', $businessId)
            ->when($locationId, fn($q) => $q->where('business_location_id', $locationId))
            ->orderBy('name')
            ->get();

        $techSchedules = DB::table('autoservice_technician_schedules as s')
            ->leftJoin('autoservice_jobs as j', 'j.id', '=', 's.job_id')
            ->leftJoin('autoservice_mechanics as m', 'm.id', '=', 's.technician_id')
            ->where('s.business_id', $businessId)
            ->when($locationId, fn($q) => $q->where('s.business_location_id', $locationId))
            ->whereDate('s.planned_date', $date)
            ->select('s.*','j.job_no','m.name as technician_name')
            ->orderBy('s.start_time')
            ->get();

        $baySchedules = DB::table('autoservice_bay_schedules as s')
            ->leftJoin('autoservice_jobs as j', 'j.id', '=', 's.job_id')
            ->leftJoin('autoservice_bays as b', 'b.id', '=', 's.bay_id')
            ->where('s.business_id', $businessId)
            ->when($locationId, fn($q) => $q->where('s.business_location_id', $locationId))
            ->whereDate('s.planned_date', $date)
            ->select('s.*','j.job_no','b.name as bay_name')
            ->orderBy('s.start_time')
            ->get();

        $partsReservations = DB::table('autoservice_parts_reservations as r')
            ->leftJoin('autoservice_jobs as j', 'j.id', '=', 'r.job_id')
            ->where('r.business_id', $businessId)
            ->when($locationId, fn($q) => $q->where('r.business_location_id', $locationId))
            ->whereDate('r.required_date', $date)
            ->select('r.*','j.job_no')
            ->orderBy('r.required_date')
            ->get();

        $capacity = [
            'planned_jobs' => $techSchedules->pluck('job_id')->filter()->unique()->count(),
            'planned_technicians' => $techSchedules->pluck('technician_id')->filter()->unique()->count(),
            'planned_bays' => $baySchedules->pluck('bay_id')->filter()->unique()->count(),
            'reserved_parts' => $partsReservations->count(),
            'open_jobs' => $jobs->count(),
        ];

        return view('autoservice::workshop_planning.index', compact(
            'jobs','technicians','bays','techSchedules','baySchedules','partsReservations','capacity','date','locationId'
        ));
    }

    public function storeTechnicianSchedule(Request $request)
    {
        $data = $request->validate([
            'job_id' => 'required|integer',
            'technician_id' => 'required|integer',
            'planned_date' => 'required|date',
            'start_time' => 'nullable|string|max:20',
            'end_time' => 'nullable|string|max:20',
            'notes' => 'nullable|string|max:500',
        ]);
        $data['business_id'] = $this->businessId();
        $data['business_location_id'] = $this->locationId($request);
        $data['status'] = 'planned';
        $data['created_by'] = auth()->id();
        $data['created_at'] = now();
        $data['updated_at'] = now();
        DB::table('autoservice_technician_schedules')->insert($data);
        $this->timeline($data['job_id'], 'technician_schedule', 'Technician schedule added for '.$data['planned_date']);
        return back()->with('status', 'Technician schedule saved.');
    }

    public function storeBaySchedule(Request $request)
    {
        $data = $request->validate([
            'job_id' => 'required|integer',
            'bay_id' => 'required|integer',
            'planned_date' => 'required|date',
            'start_time' => 'nullable|string|max:20',
            'end_time' => 'nullable|string|max:20',
            'notes' => 'nullable|string|max:500',
        ]);
        $data['business_id'] = $this->businessId();
        $data['business_location_id'] = $this->locationId($request);
        $data['status'] = 'reserved';
        $data['created_by'] = auth()->id();
        $data['created_at'] = now();
        $data['updated_at'] = now();
        DB::table('autoservice_bay_schedules')->insert($data);
        $this->timeline($data['job_id'], 'bay_schedule', 'Bay reserved for '.$data['planned_date']);
        return back()->with('status', 'Bay schedule saved.');
    }

    public function storePartsReservation(Request $request)
    {
        $data = $request->validate([
            'job_id' => 'required|integer',
            'product_id' => 'nullable|integer',
            'part_name' => 'required|string|max:191',
            'quantity' => 'required|numeric|min:0.001',
            'required_date' => 'required|date',
            'notes' => 'nullable|string|max:500',
        ]);
        $data['business_id'] = $this->businessId();
        $data['business_location_id'] = $this->locationId($request);
        $data['status'] = 'reserved';
        $data['created_by'] = auth()->id();
        $data['created_at'] = now();
        $data['updated_at'] = now();
        DB::table('autoservice_parts_reservations')->insert($data);
        $this->timeline($data['job_id'], 'parts_reservation', 'Parts reserved: '.$data['part_name'].' x '.$data['quantity']);
        return back()->with('status', 'Parts reservation saved.');
    }

    public function storeJobPlan(Request $request)
    {
        $data = $request->validate([
            'job_id' => 'required|integer',
            'planned_start_date' => 'required|date',
            'planned_completion_date' => 'nullable|date',
            'priority' => 'nullable|string|max:50',
            'planning_notes' => 'nullable|string|max:1000',
        ]);
        $data['business_id'] = $this->businessId();
        $data['business_location_id'] = $this->locationId($request);
        $data['created_by'] = auth()->id();
        $data['created_at'] = now();
        $data['updated_at'] = now();
        DB::table('autoservice_workshop_plans')->updateOrInsert(
            ['business_id' => $data['business_id'], 'job_id' => $data['job_id']],
            $data
        );
        $this->timeline($data['job_id'], 'job_plan', 'Workshop plan updated.');
        return back()->with('status', 'Job plan saved.');
    }

    protected function timeline($jobId, $type, $note)
    {
        if (!$jobId) return;
        if (!DB::getSchemaBuilder()->hasTable('autoservice_timelines')) return;
        DB::table('autoservice_timelines')->insert([
            'business_id' => $this->businessId(),
            'business_location_id' => session('business_location_id'),
            'job_id' => $jobId,
            'event_type' => $type,
            'description' => $note,
            'created_by' => auth()->id(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
