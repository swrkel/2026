<?php

namespace Modules\PetroGeneral\Http\Controllers\Pumper;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\PetroGeneral\Http\Controllers\PumpOperatorAssignmentController;

class PumperAssignmentConfirmController extends Controller
{
    public function show($assignmentId, Request $request)
    {
        return app(PumpOperatorAssignmentController::class)->confirmAssignment($assignmentId, $request);
    }

    public function store($assignmentId, Request $request)
    {
        return app(PumpOperatorAssignmentController::class)->postConfirmAssignment($assignmentId, $request);
    }
}
