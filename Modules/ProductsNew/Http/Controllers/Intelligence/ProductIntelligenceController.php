<?php
namespace Modules\ProductsNew\Http\Controllers\Intelligence;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\ProductsNew\Services\Intelligence\ProductIntelligenceService;

class ProductIntelligenceController extends Controller
{
    public function __construct(protected ProductIntelligenceService $service) {}

    public function index(Request $request)
    {
        $summary = $this->service->dashboard($request->all());
        $healthItems = $this->service->healthItems(50, $request->business_id);
        return view('productsnew::intelligence.dashboard.index', compact('summary','healthItems'));
    }
}
