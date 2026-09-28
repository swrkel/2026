<?php

namespace Modules\PetroGeneral\Http\Controllers\Pumper;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\PetroGeneral\Http\Controllers\PumpOperatorActionsController;

class PumperReceivePumpController extends Controller
{
    public function index(Request $request)
    {
        return app(PumpOperatorActionsController::class)->getReceivePump($request);
    }
}
