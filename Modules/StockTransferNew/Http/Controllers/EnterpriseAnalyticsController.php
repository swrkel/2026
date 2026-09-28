<?php

namespace Modules\StockTransferNew\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\StockTransferNew\Services\EnterpriseAnalyticsService;

class EnterpriseAnalyticsController extends Controller
{
    protected EnterpriseAnalyticsService $analytics;

    public function __construct(EnterpriseAnalyticsService $analytics)
    {
        $this->analytics = $analytics;
    }

    public function index(Request $request)
    {
        $filters = $request->only(['business_id', 'location_id', 'store_id', 'from', 'to']);
        $dashboard = $this->analytics->dashboard($filters);

        return view('stocktransfernew::enterprise_analytics.index', compact('dashboard', 'filters'));
    }

    public function bottlenecks(Request $request)
    {
        $filters = $request->only(['business_id', 'location_id', 'store_id', 'from', 'to']);
        $created = $this->analytics->detectBottlenecks($filters);

        return redirect()->back()->with('status', count($created) . ' bottleneck exception(s) detected/reviewed.');
    }

    public function snapshot(Request $request)
    {
        $filters = $request->only(['business_id', 'location_id', 'store_id', 'from', 'to']);
        $count = $this->analytics->createSnapshot($filters, optional($request->user())->id);

        return redirect()->back()->with('status', $count . ' analytics snapshot record(s) created.');
    }

    public function export(Request $request)
    {
        $csv = $this->analytics->csv($request->only(['business_id', 'location_id', 'store_id', 'from', 'to']));

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="stocktransfer_enterprise_analytics.csv"',
        ]);
    }
}
