<?php

namespace Modules\PetroGeneral\Http\Controllers\Pump;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\PetroGeneral\Http\Controllers\PumpController;

class PumpTestingDetailController extends Controller
{
    public function index(Request $request)
    {
        return app(PumpController::class)->getTestingDetails($request);
    }
}
