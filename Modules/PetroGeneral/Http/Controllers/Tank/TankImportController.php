<?php

namespace Modules\PetroGeneral\Http\Controllers\Tank;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\PetroGeneral\Http\Controllers\FuelTankController;

class TankImportController extends Controller
{
    public function create(Request $request)
    {
        return app(FuelTankController::class)->import($request);
    }

    public function store(Request $request)
    {
        return app(FuelTankController::class)->saveImport($request);
    }
}
