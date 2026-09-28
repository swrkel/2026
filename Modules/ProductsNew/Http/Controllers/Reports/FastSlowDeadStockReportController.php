<?php
namespace Modules\ProductsNew\Http\Controllers\Reports;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\ProductsNew\Services\Report\FastSlowDeadStockReportService;
class FastSlowDeadStockReportController extends Controller
{
    public function __construct(protected FastSlowDeadStockReportService $service) {}
    public function index(Request $request)
    {
        $rows = $this->service->rows($request->all());
        $title = 'Fast / Slow / Dead Stock';
        $description = $this->service->description();
        return view('productsnew::reports.fast_slow_dead_stock', compact('rows','title','description'));
    }
}
