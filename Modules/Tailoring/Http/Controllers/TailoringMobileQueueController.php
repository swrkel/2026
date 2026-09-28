<?php

namespace Modules\Tailoring\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Tailoring\Services\TailoringMobileQueueService;

class TailoringMobileQueueController extends Controller
{
    protected TailoringMobileQueueService $service;

    public function __construct(TailoringMobileQueueService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $filters = $request->all();
        $summary = $this->service->summary($filters);
        $records = $this->service->list($filters);

        return view('tailoring::mobile_queues.index', compact('filters', 'summary', 'records'));
    }

    public function store(Request $request)
    {
        return redirect()->back()->with('status', __('tailoring::messages.saved_successfully'));
    }
}
