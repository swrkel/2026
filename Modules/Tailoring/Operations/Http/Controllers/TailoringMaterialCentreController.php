<?php
namespace Modules\Tailoring\Operations\Http\Controllers;
use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Modules\Tailoring\Operations\Services\TailoringOperationsService;
class TailoringMaterialCentreController extends Controller
{
    protected $service;
    public function __construct(TailoringOperationsService $service) { $this->service = $service; }
    public function index(Request $request)
    {
        $summary = $this->service->summary('TailoringMaterialCentreController', $request);
        return view('tailoring::material_centre.index', compact('summary'));
    }
}
