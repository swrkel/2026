<?php
namespace Modules\ProductsNew\Http\Controllers\Reports;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\ProductsNew\Services\Report\ProductExpiryReportService;
class ExpiryReportController extends Controller
{
    public function __construct(protected ProductExpiryReportService $service) {}
    public function index(Request $request)
    {
        $rows = $this->service->rows($request->all());
        $title = 'Expiry Report';
        $description = $this->service->description();
        return view('productsnew::reports.expiry', compact('rows','title','description'));
    }
}
