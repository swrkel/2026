<?php
namespace Modules\ProductsNew\Http\Controllers\Reports;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\ProductsNew\Services\Report\PriceHistoryAnalyticsService;
class PriceHistoryAnalyticsController extends Controller
{
    public function __construct(protected PriceHistoryAnalyticsService $service) {}
    public function index(Request $request)
    {
        $rows = $this->service->rows($request->all());
        $title = 'Price History Analytics';
        $description = $this->service->description();
        return view('productsnew::reports.price_history_analytics', compact('rows','title','description'));
    }
}
