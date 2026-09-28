<?php

namespace Modules\DistributionNew\Http\Controllers\ServerTesting;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\DistributionNew\Services\ServerTesting\DisnewServerTestingService;

class DisnewServerTestingController extends Controller
{
    protected DisnewServerTestingService $service;

    public function __construct(DisnewServerTestingService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $summary = $this->service->summary((int) $request->session()->get('user.business_id'));
        return view('distributionnew::server_testing.index', compact('summary'));
    }

    public function run(Request $request)
    {
        $businessId = (int) $request->session()->get('user.business_id');
        $result = $this->service->runFullCheck($businessId, (int) optional($request->user())->id);
        return response()->json($result);
    }

    public function downloadChecklist(Request $request)
    {
        $businessId = (int) $request->session()->get('user.business_id');
        $content = $this->service->testingChecklist($businessId);
        return response($content, 200, [
            'Content-Type' => 'text/plain',
            'Content-Disposition' => 'attachment; filename="disnew_server_testing_checklist.txt"',
        ]);
    }
}
