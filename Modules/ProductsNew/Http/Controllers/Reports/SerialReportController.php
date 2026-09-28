<?php
namespace Modules\ProductsNew\Http\Controllers\Reports;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\ProductsNew\Services\Report\SerialReportService;
class SerialReportController extends Controller
{
    public function __construct(protected SerialReportService $service) {}
    public function index(Request $request)
    {
        $rows = $this->service->rows($request->all());
        $title = 'Serial Number Report';
        $description = $this->service->description();
        return view('productsnew::reports.serial', compact('rows','title','description'));
    }
}
