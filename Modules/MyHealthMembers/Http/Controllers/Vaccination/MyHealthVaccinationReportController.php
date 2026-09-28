<?php

namespace Modules\MyHealthMembers\Http\Controllers\Vaccination;

use Illuminate\Routing\Controller;
use Modules\MyHealthMembers\Entities\MyHealthVaccinationRecord;

class MyHealthVaccinationReportController extends Controller
{
    public function index()
    {
        $today = now()->toDateString();
        return view('myhealthmembers::vaccination.reports.index', [
            'given_today' => MyHealthVaccinationRecord::whereDate('date_given', $today)->count(),
            'due' => MyHealthVaccinationRecord::whereDate('next_due_date', '>=', $today)->count(),
            'overdue' => MyHealthVaccinationRecord::whereDate('next_due_date', '<', $today)->count(),
            'records' => MyHealthVaccinationRecord::orderByDesc('date_given')->limit(50)->get(),
        ]);
    }
}
