<?php

namespace Modules\HotelManagement\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\HotelManagement\Services\ProductionHardeningService;

class ProductionHardeningController extends Controller
{
    protected ProductionHardeningService $service;

    public function __construct(ProductionHardeningService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $audit = $this->service->buildAudit();
        return view('hotelmanagement::production_hardening.index', compact('audit'));
    }
}
