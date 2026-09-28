<?php

namespace Modules\PetroGeneral\Http\Controllers\Tank;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\PetroGeneral\Http\Controllers\FuelTankController;

class TankEditController extends Controller
{
    public function edit($id)
    {
        return app(FuelTankController::class)->edit($id);
    }
}
