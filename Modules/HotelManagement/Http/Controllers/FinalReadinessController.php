<?php

namespace Modules\HotelManagement\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\HotelManagement\Services\FinalReadinessService;

class FinalReadinessController extends Controller
{
    protected FinalReadinessService $service;

    public function __construct(FinalReadinessService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $readiness = $this->service->buildReport();
        return view('hotelmanagement::final_readiness.index', compact('readiness'));
    }
}
