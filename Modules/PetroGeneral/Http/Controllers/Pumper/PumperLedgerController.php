<?php

namespace Modules\PetroGeneral\Http\Controllers\Pumper;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\PetroGeneral\Http\Controllers\PumpOperatorController;

class PumperLedgerController extends Controller
{
    public function index(Request $request)
    {
        return app(PumpOperatorController::class)->getLedger($request);
    }
}
