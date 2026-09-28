<?php
namespace Modules\ProductsNew\Http\Controllers\Reports;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\ProductsNew\Services\Report\ProductMovementReportService;
class ProductMovementReportController extends Controller
{
    public function __construct(protected ProductMovementReportService $service) {}
    public function index(Request $request)
    {
        $rows = $this->service->rows($request->all());
        $title = 'Product Movement Report';
        $description = $this->service->description();
        return view('productsnew::reports.product_movement', compact('rows','title','description'));
    }
}
