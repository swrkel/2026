<?php

namespace Modules\MyHealthMembers\Http\Controllers\AiClinical;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\MyHealthMembers\Services\MyHealthAiClinicalAssistantService;

class MyHealthAiClinicalTimelineController extends Controller
{
    public function index(Request $request, MyHealthAiClinicalAssistantService $service)
    {
        $memberCode = $request->get('member_code');
        $timeline = $service->timeline($memberCode);

        return view('myhealthmembers::ai_clinical.timeline.index', compact('timeline', 'memberCode'));
    }
}
