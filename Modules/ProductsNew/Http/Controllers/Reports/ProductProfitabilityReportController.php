<?php
namespace Modules\ProductsNew\Http\Controllers\Reports;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\ProductsNew\Services\Report\ProductProfitabilityReportService;
class ProductProfitabilityReportController extends Controller
{
    public function __construct(protected ProductProfitabilityReportService $service) {}
    public function index(Request $request)
    {
        $rows = $this->service->rows($request->all());
        $title = 'Product Profitability Report';
        $description = $this->service->description();
        return view('productsnew::reports.product_profitability', compact('rows','title','description'));
    }
}
