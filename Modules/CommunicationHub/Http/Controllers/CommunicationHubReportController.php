<?php

namespace Modules\CommunicationHub\Http\Controllers;

use Illuminate\Routing\Controller;

use Illuminate\Http\Request;
use Modules\CommunicationHub\Entities\CommunicationHubMessage;
use Modules\CommunicationHub\Services\Reports\CommunicationHubReportService;

class CommunicationHubReportController extends Controller
{
    public function index(Request $request, CommunicationHubReportService $reports)
    {
        $summary = $reports->summary($request->all());
        $messages = CommunicationHubMessage::latest()->limit(100)->get();
        return view('communicationhub::reports.index', compact('summary','messages'));
    }

    public function export(Request $request)
    {
        $rows = CommunicationHubMessage::latest()->limit(1000)->get(['id','channel','recipient','status','cost','created_at']);
        $csv = "ID,Channel,Recipient,Status,Cost,Created At
";
        foreach ($rows as $row) {
            $csv .= implode(',', [$row->id,$row->channel,$row->recipient,$row->status,$row->cost,$row->created_at]) . "
";
        }
        return response($csv, 200, ['Content-Type'=>'text/csv','Content-Disposition'=>'attachment; filename=communicationhub-report.csv']);
    }
}
