<?php

namespace Modules\AutoService\Http\Controllers;

use Illuminate\Http\Request;
use Modules\AutoService\Services\AutoServiceAdvancedVehicleHistoryService;

class AdvancedVehicleHistoryController extends AutoServiceBaseController
{
    protected AutoServiceAdvancedVehicleHistoryService $history;

    public function __construct(AutoServiceAdvancedVehicleHistoryService $history)
    {
        parent::__construct();
        $this->history = $history;
    }

    public function index(Request $request)
    {
        $filters = $request->only(['registration_no','vin','customer_id','make']);
        $vehicles = $this->history->searchVehicles($filters);
        return view('autoservice::advanced_vehicle_history.index', compact('vehicles','filters'));
    }

    public function show(Request $request, int $vehicle)
    {
        $filters = $request->only(['from_date','to_date','status','part','movement_type','labour','document_type']);
        $data = $this->history->buildHistory($vehicle, $filters);
        abort_if(empty($data['vehicle']), 404);
        return view('autoservice::advanced_vehicle_history.show', $data + ['filters' => $filters]);
    }

    public function exportParts(Request $request, int $vehicle)
    {
        $filters = $request->only(['from_date','to_date','part','movement_type']);
        $csv = $this->history->exportPartsCsv($vehicle, $filters);
        $file = 'vehicle_'.$vehicle.'_parts_history_'.date('Ymd_His').'.csv';
        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="'.$file.'"',
        ]);
    }
}
