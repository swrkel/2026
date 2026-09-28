<?php

namespace Modules\Audit\Http\Controllers;

use Illuminate\Routing\Controller;
use Modules\Audit\Models\AuditSchedule;
use Modules\Audit\Services\ModuleRegistry;

class ScheduleController extends Controller
{
    public function index(ModuleRegistry $registry)
    {
        $businessId = session('user.business_id');

        $schedules = AuditSchedule::query()
            ->when($businessId, function ($query) use ($businessId) {
                $query->where('business_id', $businessId);
            })
            ->latest()
            ->get();

        return view('audit::schedules.index', [
            'schedules' => $schedules,
            'modules' => $registry->modules(),
            'schedulerEnabled' => (bool) config('audit.scheduled.enabled', true),
        ]);
    }

    public function store(ModuleRegistry $registry)
    {
        $validated = request()->validate([
            'name' => 'required|string|max:150',
            'frequency' => 'required|in:hourly,daily,weekly,monthly',
            'run_time' => 'nullable|date_format:H:i',
            'modules' => 'required|array|min:1',
            'modules.*' => 'required|string|max:100',
        ]);

        $allowedModules = collect($registry->modules())->map(function ($module) {
            return (string) $module;
        })->all();

        $modules = array_values(array_intersect($validated['modules'], $allowedModules));
        if (empty($modules)) {
            return back()->withErrors(['modules' => 'Select at least one valid Audit module.'])->withInput();
        }

        AuditSchedule::create([
            'business_id' => session('user.business_id'),
            'name' => $validated['name'],
            'frequency' => $validated['frequency'],
            'run_time' => $validated['run_time'] ?? null,
            'modules' => $modules,
            'is_enabled' => request('is_enabled', '1') === '1',
            'created_by' => auth()->id(),
        ]);

        return back()->with('success', 'Audit schedule created.');
    }

    public function toggle(AuditSchedule $schedule)
    {
        $businessId = session('user.business_id');
        if ($businessId && (int) $schedule->business_id !== (int) $businessId) {
            abort(403, 'This Audit schedule belongs to a different business.');
        }

        $schedule->update(['is_enabled' => ! $schedule->is_enabled]);

        return back()->with('success', 'Schedule updated.');
    }
}
