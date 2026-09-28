<?php

namespace Modules\PetroGeneral\Http\Controllers\Pumper;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\PetroGeneral\Http\Controllers\PumpOperatorActionsController;
use Modules\PetroGeneral\Http\Controllers\PumpOperatorAssignmentController;

class PumperAssignmentActionController extends Controller
{
    public function postAssignment($pumpId, $pumpOperatorId, Request $request)
    {
        return app(PumpOperatorActionsController::class)->postPumperAssignment($pumpId, $pumpOperatorId, $request);
    }

    public function getAssignment($pumpId, $pumpOperatorId, Request $request)
    {
        return app(PumpOperatorAssignmentController::class)->getPumperAssignment($pumpId, $pumpOperatorId, $request);
    }
}
