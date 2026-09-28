<?php

namespace Modules\MyHealthMembers\Http\Controllers\AiClinical;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\MyHealthMembers\Services\MyHealthAiClinicalAssistantService;

class MyHealthAiClinicalDashboardController extends Controller
{
    public function index(Request $request, MyHealthAiClinicalAssistantService $service)
    {
        $data = $service->dashboard($request->only(['date_from', 'date_to', 'member_code', 'severity', 'status']));

        return view('myhealthmembers::ai_clinical.dashboard', compact('data'));
    }
}
