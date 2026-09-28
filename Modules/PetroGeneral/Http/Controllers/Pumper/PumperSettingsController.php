<?php

namespace Modules\PetroGeneral\Http\Controllers\Pumper;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\PetroGeneral\Http\Controllers\PumpOperatorController;

class PumperSettingsController extends Controller
{
    public function edit(Request $request)
    {
        return app(PumpOperatorController::class)->dashboard_settings($request);
    }

    public function store(Request $request)
    {
        return app(PumpOperatorController::class)->store_settings($request);
    }
}
