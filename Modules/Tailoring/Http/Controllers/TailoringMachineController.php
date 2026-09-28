<?php

namespace Modules\Tailoring\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Tailoring\Services\TailoringMachineService;

class TailoringMachineController extends Controller
{
    protected TailoringMachineService $service;

    public function __construct(TailoringMachineService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $filters = $request->all();
        $summary = $this->service->summary($filters);
        $records = $this->service->list($filters);

        return view('tailoring::machines.index', compact('filters', 'summary', 'records'));
    }

    public function store(Request $request)
    {
        return redirect()->back()->with('status', __('tailoring::messages.saved_successfully'));
    }
}
