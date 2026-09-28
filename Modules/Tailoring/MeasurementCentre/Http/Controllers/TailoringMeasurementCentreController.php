<?php
namespace Modules\Tailoring\MeasurementCentre\Http\Controllers;
use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Modules\Tailoring\MeasurementCentre\Services\TailoringMeasurementCentreService;

class TailoringMeasurementCentreController extends Controller
{
    protected $service;
    public function __construct(TailoringMeasurementCentreService $service) { $this->service = $service; }
    public function index(Request $request) { $summary = $this->service->summary($request); return view('tailoring::measurement_centre.index', compact('summary')); }
    public function create() { return view('tailoring::measurement_centre.create'); }
    public function compare($customerId) { $profiles = $this->service->profilesForCustomer($customerId); return view('tailoring::measurement_centre.compare', compact('profiles','customerId')); }
}
