<?php

namespace Modules\MyHealthMembers\Http\Controllers\AiClinical;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\MyHealthMembers\Services\MyHealthAiClinicalAssistantService;

class MyHealthAiClinicalAlertController extends Controller
{
    public function index(Request $request, MyHealthAiClinicalAssistantService $service)
    {
        $data = $service->alerts($request->only(['date_from', 'date_to', 'member_code', 'severity', 'status', 'alert_type']));

        return view('myhealthmembers::ai_clinical.alerts.index', compact('data'));
    }

    public function acknowledge($alertId, Request $request)
    {
        $connection = config('myhealthmembers.central_connection');

        if (Schema::connection($connection)->hasTable('myhealth_clinical_alerts')) {
            DB::connection($connection)->table('myhealth_clinical_alerts')
                ->where('id', $alertId)
                ->update([
                    'status' => 'acknowledged',
                    'acknowledged_by' => auth()->id(),
                    'acknowledged_at' => now(),
                    'updated_at' => now(),
                ]);
        }

        return redirect()->back()->with('status', ['success' => 1, 'msg' => 'Clinical alert acknowledged successfully.']);
    }
}
