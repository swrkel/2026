<?php

namespace Modules\PetroGeneral\Http\Controllers\Pump;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\PetroGeneral\Http\Controllers\PumpController;

class PumpImportController extends Controller
{
    public function create(Request $request)
    {
        return app(PumpController::class)->importPumps($request);
    }

    public function store(Request $request)
    {
        return app(PumpController::class)->saveImport($request);
    }
}
