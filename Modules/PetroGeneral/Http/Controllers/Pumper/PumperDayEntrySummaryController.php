<?php

namespace Modules\PetroGeneral\Http\Controllers\Pumper;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\PetroGeneral\Http\Controllers\PumperDayEntryController;

class PumperDayEntrySummaryController extends Controller
{
    public function dayEntry(Request $request)
    {
        return app(PumperDayEntryController::class)->getPumperDayEntrySummary($request);
    }

    public function closingShift(Request $request)
    {
        return app(PumperDayEntryController::class)->getClosingShiftSummary($request);
    }
}
