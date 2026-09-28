<?php

namespace Modules\PetroGeneral\Http\Controllers\Tank;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\PetroGeneral\Http\Controllers\FuelTankController;

class TankShowController extends Controller
{
    public function show($id)
    {
        return app(FuelTankController::class)->show($id);
    }
}
