<?php
namespace Modules\ProductsNew\Http\Controllers\Reports;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\ProductsNew\Services\Report\InventoryTurnoverReportService;
class InventoryTurnoverReportController extends Controller
{
    public function __construct(protected InventoryTurnoverReportService $service) {}
    public function index(Request $request)
    {
        $rows = $this->service->rows($request->all());
        $title = 'Inventory Turnover Report';
        $description = $this->service->description();
        return view('productsnew::reports.inventory_turnover', compact('rows','title','description'));
    }
}
