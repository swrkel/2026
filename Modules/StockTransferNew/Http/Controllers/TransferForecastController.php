<?php

namespace Modules\StockTransferNew\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\StockTransferNew\Entities\ReplenishmentSuggestion;
use Modules\StockTransferNew\Services\TransferForecastService;

class TransferForecastController extends Controller
{
    protected TransferForecastService $service;

    public function __construct(TransferForecastService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $businessId = (int) $request->session()->get('user.business_id');
        $data = $this->service->dashboard($businessId, $request->all());

        return view('stocktransfernew::forecasting.index', compact('data'));
    }

    public function generate(Request $request)
    {
        $businessId = (int) $request->session()->get('user.business_id');
        $created = $this->service->generateSuggestions($businessId, $request->all());

        return redirect()->back()->with('status', $created . ' replenishment suggestions generated.');
    }

    public function approveSuggestion(Request $request, ReplenishmentSuggestion $suggestion)
    {
        $businessId = (int) $request->session()->get('user.business_id');
        abort_if((int) $suggestion->business_id !== $businessId, 403);
        $this->service->approveSuggestion($suggestion);

        return redirect()->back()->with('status', 'Suggestion approved.');
    }

    public function closeSuggestion(Request $request, ReplenishmentSuggestion $suggestion)
    {
        $businessId = (int) $request->session()->get('user.business_id');
        abort_if((int) $suggestion->business_id !== $businessId, 403);
        $this->service->closeSuggestion($suggestion, $request->input('reason'));

        return redirect()->back()->with('status', 'Suggestion closed.');
    }

    public function export(Request $request)
    {
        $businessId = (int) $request->session()->get('user.business_id');
        $data = $this->service->dashboard($businessId, $request->all());

        $headers = ['Product ID', 'Location ID', 'Store ID', 'Current Stock', 'Minimum Stock', 'Suggested Qty', 'Priority', 'Status'];
        $callback = function () use ($data, $headers) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $headers);
            foreach ($data['suggestions'] as $row) {
                fputcsv($out, [$row->product_id, $row->business_location_id, $row->store_id, $row->current_stock, $row->minimum_stock, $row->suggested_qty, $row->priority, $row->status]);
            }
            fclose($out);
        };

        return response()->streamDownload($callback, 'stock_transfer_forecast_suggestions.csv');
    }
}
