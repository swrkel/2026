<?php
namespace Modules\ProductsNew\Http\Controllers\Reports;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\ProductsNew\Services\Report\ReorderRecommendationReportService;
class ReorderRecommendationReportController extends Controller
{
    public function __construct(protected ReorderRecommendationReportService $service) {}
    public function index(Request $request)
    {
        $rows = $this->service->rows($request->all());
        $title = 'Reorder Recommendation';
        $description = $this->service->description();
        return view('productsnew::reports.reorder_recommendation', compact('rows','title','description'));
    }
}
