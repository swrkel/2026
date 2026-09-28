<?php

namespace Modules\PetroGeneral\Http\Controllers\Pumper;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\PetroGeneral\Http\Controllers\PumpOperatorController;

class PumperDashboardController extends Controller
{
    public function index(Request $request)
    {
        return app(PumpOperatorController::class)->dashboard($request);
    }

    public function settings(Request $request)
    {
        return app(PumpOperatorController::class)->setting_dash($request);
    }

    public function data(Request $request)
    {
        return app(PumpOperatorController::class)->getDashboardData($request);
    }
}
