<?php

namespace Modules\StockTakingNew\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\StockTakingNew\Entities\StockTakeSchedule;
use Modules\StockTakingNew\Entities\StockTakeTemplate;
use Modules\StockTakingNew\Services\MasterDataBridgeService;
use Modules\StockTakingNew\Services\ScheduleService;
use Modules\StockTakingNew\Services\TenantScopeService;

class ScheduleController extends Controller
{
    public function index(Request $request, TenantScopeService $scope, MasterDataBridgeService $masters)
    {
        $businessId = $scope->businessId($request);
        abort_unless($businessId, 403);

        $scheduleQuery = StockTakeSchedule::where('business_id', $businessId);
        $scope->applyLocationScope($scheduleQuery);
        $schedules = $scheduleQuery->latest()->paginate(30);
        $locations = $masters->locations($businessId);
        $templates = StockTakeTemplate::where('business_id', $businessId)
            ->where('is_active', 1)->orderBy('name')->pluck('name', 'id');

        return view('stocktakingnew::schedules.index', compact('schedules', 'locations', 'templates'));
    }

    public function store(
        Request $request,
        TenantScopeService $scope,
        MasterDataBridgeService $masters,
        ScheduleService $schedules
    ) {
        $businessId = $scope->businessId($request);
        abort_unless($businessId, 403);

        $data = $request->validate([
            'name' => 'required|string|max:160',
            'location_id' => 'required|integer',
            'store_id' => 'nullable|integer',
            'template_id' => 'nullable|integer',
            'frequency' => 'required|in:daily,weekly,monthly,quarterly',
            'day_of_week' => 'nullable|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'day_of_month' => 'nullable|integer|min:1|max:31',
            'run_time' => 'nullable|date_format:H:i',
        ]);

        abort_unless(array_key_exists((int) $data['location_id'], $masters->locations($businessId)), 422, 'Invalid business location.');
        if (! empty($data['store_id'])) {
            abort_unless(array_key_exists((int) $data['store_id'], $masters->stores($businessId, (int) $data['location_id'])), 422, 'Invalid store for this location.');
        }
        if (! empty($data['template_id'])) {
            abort_unless(StockTakeTemplate::where('business_id', $businessId)->whereKey((int) $data['template_id'])->exists(), 422, 'Invalid template.');
        }

        $data['business_id'] = $businessId;
        $data['is_active'] = true;
        $data['created_by'] = auth()->id();
        $data['next_run_at'] = $schedules->nextRun($data);
        StockTakeSchedule::create($data);

        return back()->with('status', 'Schedule created.');
    }

    public function toggle(StockTakeSchedule $schedule, Request $request, TenantScopeService $scope, ScheduleService $schedules)
    {
        $scope->assertBusinessRecord($schedule, $scope->businessId($request));
        $active = ! $schedule->is_active;
        $schedule->update([
            'is_active' => $active,
            'next_run_at' => $active ? $schedules->nextRun($schedule->toArray()) : null,
        ]);

        return back()->with('status', $active ? 'Schedule activated.' : 'Schedule paused.');
    }

    public function destroy(StockTakeSchedule $schedule, Request $request, TenantScopeService $scope)
    {
        $scope->assertBusinessRecord($schedule, $scope->businessId($request));
        $schedule->delete();

        return back()->with('status', 'Schedule deleted.');
    }
}
