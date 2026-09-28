<?php

namespace Modules\DistributionNew\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\DistributionNew\Services\ServerTesting\DisnewServerTestingFixService;

class DisnewServerTestingFixController extends Controller
{
    protected DisnewServerTestingFixService $service;

    public function __construct(DisnewServerTestingFixService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $businessId = (int) ($request->session()->get('user.business_id') ?? 0);
        $locationId = $request->get('location_id');
        $results = $this->service->run($businessId, $locationId ? (int) $locationId : null);

        return view('distributionnew::server_testing.fix_pack_2', compact('results', 'businessId', 'locationId'));
    }

    public function json(Request $request)
    {
        $businessId = (int) ($request->session()->get('user.business_id') ?? 0);
        $locationId = $request->get('location_id');

        return response()->json([
            'success' => true,
            'data' => $this->service->run($businessId, $locationId ? (int) $locationId : null),
        ]);
    }
}
