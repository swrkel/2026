<?php

namespace Modules\Tailoring\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Tailoring\Services\TailoringCostingService;

class TailoringCostingController extends Controller
{
    protected TailoringCostingService $service;

    public function __construct(TailoringCostingService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $filters = $request->all();
        $summary = $this->service->summary($filters);
        $records = $this->service->list($filters);

        return view('tailoring::costing.index', compact('filters', 'summary', 'records'));
    }

    public function store(Request $request)
    {
        return redirect()->back()->with('status', __('tailoring::messages.saved_successfully'));
    }
}
