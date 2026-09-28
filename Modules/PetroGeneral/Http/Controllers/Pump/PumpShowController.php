<?php

namespace Modules\PetroGeneral\Http\Controllers\Pump;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\PetroGeneral\Http\Controllers\PumpController;

class PumpShowController extends Controller
{
    public function show(Request $request)
    {
        return app(PumpController::class)->show($request);
    }
}
