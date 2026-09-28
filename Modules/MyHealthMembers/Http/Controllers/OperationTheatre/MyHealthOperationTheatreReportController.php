<?php

namespace Modules\MyHealthMembers\Http\Controllers\OperationTheatre;

use Illuminate\Routing\Controller;
use Modules\MyHealthMembers\Entities\MyHealthOperativeRecord;
use Modules\MyHealthMembers\Entities\MyHealthSurgerySchedule;

class MyHealthOperationTheatreReportController extends Controller
{
    public function index()
    {
        return view('myhealthmembers::operation_theatre.reports.index', [
            'scheduled' => MyHealthSurgerySchedule::count(),
            'completed' => MyHealthSurgerySchedule::where('status', 'completed')->count(),
            'emergency' => MyHealthSurgerySchedule::where('priority', 'emergency')->count(),
            'records' => MyHealthOperativeRecord::count(),
            'schedules' => MyHealthSurgerySchedule::orderByDesc('scheduled_start_at')->limit(50)->get(),
        ]);
    }
}
