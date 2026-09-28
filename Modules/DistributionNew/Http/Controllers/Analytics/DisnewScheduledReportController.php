<?php

namespace Modules\DistributionNew\Http\Controllers\Analytics;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\DistributionNew\Entities\DisnewScheduledReport;
use Modules\DistributionNew\Services\Analytics\DisnewScheduledReportService;

class DisnewScheduledReportController extends Controller
{
    public function index()
    {
        $businessId = session('business.id');
        $reports = DisnewScheduledReport::where('business_id', $businessId)->latest()->paginate(30);
        return view('distributionnew::analytics.scheduled_reports.index', compact('reports'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'report_code' => 'required|string|max:80',
            'report_name' => 'required|string|max:150',
            'frequency' => 'required|string',
            'delivery_channel' => 'required|string',
        ]);
        $data['business_id'] = session('business.id');
        $data['business_location_id'] = $request->input('business_location_id');
        $data['recipient_emails'] = $request->input('recipient_emails');
        $data['recipient_mobiles'] = $request->input('recipient_mobiles');
        $data['officer_group'] = $request->input('officer_group');
        $data['created_by'] = auth()->id();
        DisnewScheduledReport::create($data);
        return back()->with('status', ['success' => 1, 'msg' => __('distributionnew::lang.scheduled_report_saved')]);
    }

    public function queue(DisnewScheduledReport $scheduledReport, DisnewScheduledReportService $service)
    {
        $service->queueDelivery($scheduledReport);
        return back()->with('status', ['success' => 1, 'msg' => __('distributionnew::lang.report_delivery_queued')]);
    }
}
