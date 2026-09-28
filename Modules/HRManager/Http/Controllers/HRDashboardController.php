<?php

namespace Modules\HRManager\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\HRManager\Models\HREmployee;
use Modules\HRManager\Models\HRAttendanceLog;

class HRDashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'employees' => HREmployee::count(),
            'active_employees' => HREmployee::where('status', 'active')->count(),
            'today_punches' => HRAttendanceLog::whereDate('attendance_date', today())->count(),
            'pending_exceptions' => HRAttendanceLog::where('status', 'pending')->count(),
        ];
        return view('hrmanager::dashboard.index', compact('stats'));
    }
}
