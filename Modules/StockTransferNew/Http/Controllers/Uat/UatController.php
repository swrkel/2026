<?php

namespace Modules\StockTransferNew\Http\Controllers\Uat;

use Illuminate\Routing\Controller;
use Modules\StockTransferNew\Services\Uat\UatChecklistService;

class UatController extends Controller
{
    public function index(UatChecklistService $service)
    {
        $checklist = $service->checklist();
        $summary = $service->summary($checklist);

        return view('stocktransfernew::uat.index', compact('checklist', 'summary'));
    }

    public function print(UatChecklistService $service)
    {
        $checklist = $service->checklist();
        $summary = $service->summary($checklist);

        return view('stocktransfernew::uat.print', compact('checklist', 'summary'));
    }
}
