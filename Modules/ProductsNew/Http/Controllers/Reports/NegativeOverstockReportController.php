<?php
namespace Modules\ProductsNew\Http\Controllers\Reports;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\ProductsNew\Services\Report\NegativeOverstockReportService;
class NegativeOverstockReportController extends Controller
{
    public function __construct(protected NegativeOverstockReportService $service) {}
    public function index(Request $request)
    {
        $rows = $this->service->rows($request->all());
        $title = 'Negative & Overstock Report';
        $description = $this->service->description();
        return view('productsnew::reports.negative_overstock', compact('rows','title','description'));
    }
}
