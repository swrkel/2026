<?php
namespace Modules\Tailoring\CustomerCentre\Http\Controllers;
use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Modules\Tailoring\CustomerCentre\Services\TailoringCustomerCentreService;

class TailoringCustomerCentreController extends Controller
{
    protected $service;
    public function __construct(TailoringCustomerCentreService $service) { $this->service = $service; }
    public function index(Request $request) { $summary = $this->service->summary($request); return view('tailoring::customer_centre.index', compact('summary')); }
    public function create() { return view('tailoring::customer_centre.create'); }
    public function show($id) { $customer = $this->service->profile($id); return view('tailoring::customer_centre.show', compact('customer')); }
}
