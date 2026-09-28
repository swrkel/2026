<?php

namespace Modules\PetroGeneral\Http\Controllers\Pumper;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\PetroGeneral\Http\Controllers\PumpOperatorController;

class PumperLoginCheckController extends Controller
{
    public function username(Request $request)
    {
        return app(PumpOperatorController::class)->checUsername($request);
    }

    public function passcode(Request $request)
    {
        return app(PumpOperatorController::class)->checPasscode($request);
    }
}
