<?php

namespace Modules\StockTransferNew\Http\Controllers\Support;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\StockTransferNew\Services\Support\RepairService;

class RepairController extends Controller
{
    protected RepairService $repairService;

    public function __construct(RepairService $repairService)
    {
        $this->repairService = $repairService;
    }

    public function index()
    {
        $repairs = $this->repairService->availableRepairs();

        return view('stocktransfernew::support.repair', compact('repairs'));
    }

    public function run(Request $request)
    {
        $request->validate([
            'repair_key' => 'required|string|max:100',
        ]);

        $result = $this->repairService->run($request->input('repair_key'));

        return redirect()->back()->with('status', $result['message'] ?? 'Repair action completed.');
    }
}
