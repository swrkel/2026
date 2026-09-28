<?php

namespace Modules\RestaurantNew\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Modules\RestaurantNew\Services\HaccpService;

class HaccpController extends Controller
{
    protected HaccpService $service;

    public function __construct(HaccpService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        return view('restaurantnew::haccp.index', [
            'summary' => $this->service->summary($request),
        ]);
    }

    public function temperatureLogs(Request $request)
    {
        return view('restaurantnew::haccp.temperature_logs', [
            'logs' => $this->service->temperatureLogs($request),
        ]);
    }

    public function storeTemperature(Request $request)
    {
        $this->service->storeTemperature($request);
        return redirect()->back()->with('status', __('restaurantnew::messages.temperature_saved'));
    }

    public function correctiveActions(Request $request)
    {
        return view('restaurantnew::haccp.corrective_actions', [
            'actions' => $this->service->correctiveActions($request),
        ]);
    }
}
