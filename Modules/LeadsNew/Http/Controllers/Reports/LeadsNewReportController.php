<?php

namespace Modules\LeadsNew\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\LeadsNew\Models\LeadsNewLead;
use Modules\LeadsNew\Models\LeadsNewFollowup;
use Modules\LeadsNew\Models\LeadsNewOpportunity;
use Modules\LeadsNew\Services\LeadsNewReportExportService;

class LeadsNewReportController extends Controller
{
    public function index()
    {
        return view('leadsnew::reports.index');
    }

    public function leadRegister(Request $request, LeadsNewReportExportService $exporter)
    {
        $rows = LeadsNewLead::query()->latest('id')->limit(1000)->get();
        if ($request->get('export') === 'csv') {
            return $exporter->csv($rows, ['lead_no'=>'Lead No','name'=>'Name','mobile'=>'Mobile','status'=>'Status'], 'leads-new-register.csv');
        }
        return view('leadsnew::reports.lead_register', compact('rows'));
    }

    public function conversion(Request $request)
    {
        $rows = LeadsNewLead::query()->selectRaw('status, count(*) as total')->groupBy('status')->get();
        return view('leadsnew::reports.conversion', compact('rows'));
    }

    public function pipeline(Request $request)
    {
        $rows = LeadsNewOpportunity::query()->latest('id')->limit(500)->get();
        return view('leadsnew::reports.pipeline', compact('rows'));
    }

    public function followups(Request $request)
    {
        $rows = LeadsNewFollowup::query()->latest('id')->limit(1000)->get();
        return view('leadsnew::reports.followups', compact('rows'));
    }
}
