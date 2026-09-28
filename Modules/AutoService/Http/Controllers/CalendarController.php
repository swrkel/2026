<?php
namespace Modules\AutoService\Http\Controllers;

use Illuminate\Http\Request;
use Modules\AutoService\Entities\AutoServiceAppointment;
use Modules\AutoService\Entities\AutoServiceJob;

class CalendarController extends AutoServiceBaseController
{
    public function index(Request $request)
    {
        $date = $request->get('date', date('Y-m-d'));
        $appointments = AutoServiceAppointment::whereDate('appointment_date', $date)->orderBy('appointment_date')->get();
        $jobs = AutoServiceJob::whereDate('job_date', $date)->orWhereDate('estimated_completion_at', $date)->orderBy('estimated_completion_at')->get();
        return view('autoservice::calendar.index', compact('date','appointments','jobs'));
    }
}
