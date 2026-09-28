<?php

namespace Modules\StockTransferNew\Http\Controllers\Uat;

use Illuminate\Routing\Controller;
use Modules\StockTransferNew\Services\Uat\DataSnapshotService;

class DataSnapshotController extends Controller
{
    public function index(DataSnapshotService $service)
    {
        $snapshot = $service->snapshot();
        return view('stocktransfernew::uat.snapshot', compact('snapshot'));
    }
}
