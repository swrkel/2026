<?php
namespace Modules\ProductsNew\Http\Controllers\Reports;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\ProductsNew\Services\Report\AbcXyzAnalysisReportService;
class AbcXyzAnalysisReportController extends Controller
{
    public function __construct(protected AbcXyzAnalysisReportService $service) {}
    public function index(Request $request)
    {
        $rows = $this->service->rows($request->all());
        $title = 'ABC / XYZ Analysis';
        $description = $this->service->description();
        return view('productsnew::reports.abc_xyz', compact('rows','title','description'));
    }
}
