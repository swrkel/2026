<?php
namespace Modules\ProductsNew\Http\Controllers\Reports;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\ProductsNew\Services\Report\CategoryBrandReportService;
class CategoryBrandReportController extends Controller
{
    public function __construct(protected CategoryBrandReportService $service) {}
    public function index(Request $request)
    {
        $rows = $this->service->rows($request->all());
        $title = 'Category & Brand Report';
        $description = $this->service->description();
        return view('productsnew::reports.category_brand', compact('rows','title','description'));
    }
}
