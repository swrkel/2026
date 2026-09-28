<?php

namespace Modules\StockTransferNew\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\StockTransferNew\Entities\PeriodClose;
use Modules\StockTransferNew\Services\Admin\PeriodCloseService;

class PeriodCloseController extends Controller
{
    protected PeriodCloseService $service;

    public function __construct(PeriodCloseService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $businessId = (int) $request->session()->get('user.business_id');
        $year = (int) $request->get('year', now()->year);
        $month = (int) $request->get('month', now()->month);
        $locationId = $request->filled('location_id') ? (int) $request->get('location_id') : null;
        $storeId = $request->filled('store_id') ? (int) $request->get('store_id') : null;

        $preview = $this->service->preview($businessId, $locationId, $storeId, $year, $month);
        $latestCloses = PeriodClose::where('business_id', $businessId)->latest('id')->limit(12)->get();

        return view('stocktransfernew::admin.period_close.index', compact('preview','latestCloses','year','month','locationId','storeId'));
    }

    public function lock(Request $request)
    {
        $businessId = (int) $request->session()->get('user.business_id');
        $close = $this->service->lockPeriod(
            $businessId,
            $request->filled('location_id') ? (int) $request->get('location_id') : null,
            $request->filled('store_id') ? (int) $request->get('store_id') : null,
            (int) $request->get('year', now()->year),
            (int) $request->get('month', now()->month),
            (int) $request->user()->id,
            $request->get('remarks')
        );

        return redirect()->back()->with($close->status === 'locked' ? 'status' : 'error', $close->status === 'locked' ? 'Stock Transfer period locked successfully.' : 'Period close blocked. Please clear listed issues.');
    }

    public function reopen(Request $request, PeriodClose $periodClose)
    {
        $this->service->reopen($periodClose, (int) $request->user()->id, $request->get('remarks'));
        return redirect()->back()->with('status', 'Stock Transfer period reopened successfully.');
    }
}
