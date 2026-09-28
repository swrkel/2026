<?php

namespace Modules\StockTransferNew\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\StockTransferNew\Services\Admin\DataQualityService;

class DataQualityController extends Controller
{
    protected DataQualityService $service;

    public function __construct(DataQualityService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $filters = [
            'business_id' => $request->get('business_id'),
            'location_id' => $request->get('location_id'),
            'store_id' => $request->get('store_id'),
            'from_date' => $request->get('from_date'),
            'to_date' => $request->get('to_date'),
        ];

        return view('stocktransfernew::admin.data_quality.index', [
            'summary' => $this->service->summary($filters),
            'filters' => $filters,
        ]);
    }

    public function export(Request $request)
    {
        $csv = $this->service->exportCsv($request->all());

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="stock_transfer_data_quality.csv"',
        ]);
    }
}
