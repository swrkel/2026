<?php
namespace Modules\AutoService\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\AutoService\Entities\AutoServiceJob;
use Modules\AutoService\Entities\AutoServiceJobMechanic;
use Modules\AutoService\Entities\AutoServiceMechanic;
use Modules\AutoService\Entities\AutoServiceTimeline;

class MechanicDashboardController extends AutoServiceBaseController
{
    public function index(Request $request)
    {
        $mechanics = AutoServiceMechanic::orderBy('name')->get();
        $mechanicId = $request->get('mechanic_id');
        $assignments = AutoServiceJobMechanic::query()->when($mechanicId, fn($q)=>$q->where('mechanic_id',$mechanicId))->orderByDesc('id')->limit(50)->get();
        $jobIds = $assignments->pluck('job_id')->filter()->unique()->values();
        $jobs = $jobIds->isNotEmpty() ? AutoServiceJob::whereIn('id',$jobIds)->orderByDesc('job_date')->get()->keyBy('id') : collect();
        $completedToday = $jobIds->isNotEmpty() ? AutoServiceJob::whereIn('id',$jobIds)->whereDate('completed_at', date('Y-m-d'))->count() : 0;
        $timeline = $jobIds->isNotEmpty() ? AutoServiceTimeline::whereIn('job_id',$jobIds)->orderByDesc('event_at')->limit(30)->get() : collect();
        return view('autoservice::mechanic_dashboard.index', compact('mechanics','mechanicId','assignments','jobs','completedToday','timeline'));
    }
}
