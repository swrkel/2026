<?php

namespace Modules\PetroGeneral\Http\Controllers\Pumper;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\PetroGeneral\Http\Controllers\PumpOperatorController;

class PumperLoginAttemptController extends Controller
{
    public function blocked()
    {
        return app(PumpOperatorController::class)->blockedPumperLoginAttempt();
    }

    public function history()
    {
        return app(PumpOperatorController::class)->pumperLoginAttemptHistory();
    }

    public function unblock(Request $request, $id)
    {
        return app(PumpOperatorController::class)->unblockPumperLoginAttempt($request, $id);
    }
}
