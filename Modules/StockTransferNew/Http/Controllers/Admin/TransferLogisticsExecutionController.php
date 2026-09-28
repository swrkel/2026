<?php

namespace Modules\StockTransferNew\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Modules\StockTransferNew\Services\Admin\TransferLogisticsExecutionService;

class TransferLogisticsExecutionController extends Controller
{
    protected TransferLogisticsExecutionService $service;

    public function __construct(TransferLogisticsExecutionService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $data = $this->service->dashboard($request->all());
        return view('stocktransfernew::admin.logistics.index', $data);
    }

    public function storeLoad(Request $request)
    {
        $id = $this->service->createLoad($request->all(), (int) auth()->id());
        return redirect()->back()->with('status', __('stocktransfernew::logistics.load_created') . ' #' . $id);
    }

    public function dispatchLoad(Request $request, int $loadId)
    {
        $this->service->dispatchLoad($loadId, $request->input('checklist', []), (int) auth()->id());
        return redirect()->back()->with('status', __('stocktransfernew::logistics.load_dispatched'));
    }

    public function receive(Request $request, int $loadId)
    {
        $this->service->receiveLoad($loadId, $request->input('checklist', []), (int) auth()->id());
        return redirect()->back()->with('status', __('stocktransfernew::logistics.load_received'));
    }

    public function consolidate(Request $request)
    {
        $ids = array_filter((array) $request->input('transfer_ids', []));
        $id = $this->service->createConsolidation($ids, $request->all(), (int) auth()->id());
        return redirect()->back()->with('status', __('stocktransfernew::logistics.consolidation_created') . ' #' . $id);
    }

    public function export(Request $request)
    {
        $rows = $this->service->export($request->all());
        $callback = function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Load No','Transfer No','Vehicle','Driver','Status','Capacity Qty','Loaded Qty','ETA','Route']);
            foreach ($rows as $row) {
                fputcsv($out, [$row->load_no, $row->transfer_no, $row->vehicle_no, $row->driver_name, $row->load_status, $row->vehicle_capacity_qty, $row->loaded_qty, $row->eta_at, $row->route_name]);
            }
            fclose($out);
        };
        return Response::stream($callback, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="stock_transfer_new_logistics.csv"',
        ]);
    }
}
