<?php

namespace Modules\Tailoring\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Tailoring\Services\TailoringCustomerExperienceService;

class TailoringCustomerExperienceController extends Controller
{
    protected TailoringCustomerExperienceService $service;

    public function __construct(TailoringCustomerExperienceService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $filters = $request->all();
        $summary = $this->service->summary($filters);
        $records = $this->service->list($filters);

        return view('tailoring::customer_experience.index', compact('filters', 'summary', 'records'));
    }

    public function store(Request $request)
    {
        return redirect()->back()->with('status', __('tailoring::messages.saved_successfully'));
    }
}
