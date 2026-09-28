<?php

namespace Modules\MyHealthMembers\Http\Controllers\AiClinical;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\MyHealthMembers\Services\MyHealthAiClinicalAssistantService;

class MyHealthAiClinicalReportController extends Controller
{
    public function index(Request $request, MyHealthAiClinicalAssistantService $service)
    {
        $data = $service->dashboard($request->only(['date_from', 'date_to']));

        return view('myhealthmembers::ai_clinical.reports.index', compact('data'));
    }

    public function medicationSafety(Request $request, MyHealthAiClinicalAssistantService $service)
    {
        $data = $service->medicationSafety($request->only(['date_from', 'date_to', 'member_code']));

        return view('myhealthmembers::ai_clinical.reports.medication_safety', compact('data'));
    }

    public function preventiveCare(Request $request, MyHealthAiClinicalAssistantService $service)
    {
        $data = $service->preventiveCare($request->only(['date_from', 'date_to', 'member_code']));

        return view('myhealthmembers::ai_clinical.reports.preventive_care', compact('data'));
    }
}
