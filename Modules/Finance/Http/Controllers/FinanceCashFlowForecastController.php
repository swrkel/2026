<?php

namespace Modules\Finance\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Finance\Entities\FinanceCashFlowForecast;

class FinanceCashFlowForecastController extends Controller
{
    public function index()
    {
        $business_id = session('business.id');

        $forecasts = FinanceCashFlowForecast::where(
            'business_id',
            $business_id
        )
        ->latest()
        ->paginate(20);

        return view(
            'finance::cash_flow_forecast.index',
            compact('forecasts')
        );
    }

    public function create()
    {
        return view(
            'finance::cash_flow_forecast.create'
        );
    }

    public function store(Request $request)
    {
        try {

            $business_id = session('business.id');

            FinanceCashFlowForecast::create([

                'business_id' =>
                    $business_id,

                'location_id' =>
                    $request->location_id,

                'forecast_no' =>
                    'CFF-' . time(),

                'forecast_type' =>
                    $request->forecast_type,

                'module' =>
                    $request->module,

                'category' =>
                    $request->category,

                'subject' =>
                    $request->subject,

                'description' =>
                    $request->description,

                'expected_date' =>
                    $request->expected_date,

                'expected_amount' =>
                    $request->expected_amount,

                'probability_percent' =>
                    $request->probability_percent,

                'status' =>
                    $request->status,

                'created_by' =>
                    auth()->id(),
            ]);

            return redirect()
                ->route(
                    'finance.cash_flow_forecast.index'
                )
                ->with('status', [
                    'success' => 1,
                    'msg' =>
                        'Cash flow forecast created successfully'
                ]);

        } catch (\Exception $e) {

            return redirect()
                ->back()
                ->with('status', [
                    'success' => 0,
                    'msg' => $e->getMessage()
                ]);
        }
    }
}