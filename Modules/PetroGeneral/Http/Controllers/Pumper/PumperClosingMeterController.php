<?php

namespace Modules\PetroGeneral\Http\Controllers\Pumper;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\PetroGeneral\Http\Controllers\PumpOperatorActionsController;

class PumperClosingMeterController extends Controller
{
    public function modal(Request $request)
    {
        return app(PumpOperatorActionsController::class)->getClosingMeterModal($request);
    }

    public function show($pumpId, Request $request)
    {
        return app(PumpOperatorActionsController::class)->getClosingMeter($pumpId, $request);
    }

    public function store($pumpId, Request $request)
    {
        return app(PumpOperatorActionsController::class)->postClosingMeter($pumpId, $request);
    }
}
