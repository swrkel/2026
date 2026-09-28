<?php

namespace Modules\PetroGeneral\Http\Controllers\Pumper;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\PetroGeneral\Http\Controllers\PumpOperatorController;

class PumperPasscodeController extends Controller
{
    public function edit(Request $request)
    {
        return app(PumpOperatorController::class)->update_passcode($request);
    }

    public function store(Request $request)
    {
        return app(PumpOperatorController::class)->store_passcode($request);
    }
}
