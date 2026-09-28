<?php
namespace Modules\ProductsNew\Http\Controllers\Reports;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\ProductsNew\Services\Report\ProductAgingReportService;
class ProductAgingReportController extends Controller
{
    public function __construct(protected ProductAgingReportService $service) {}
    public function index(Request $request)
    {
        $rows = $this->service->rows($request->all());
        $title = 'Product Aging Report';
        $description = $this->service->description();
        return view('productsnew::reports.product_aging', compact('rows','title','description'));
    }
}
