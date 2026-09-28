<?php

namespace Modules\PetroGeneral\Http\Controllers\Tank;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\PetroGeneral\Http\Controllers\FuelTankController;

class TankDeleteController extends Controller
{
    public function destroy($id)
    {
        return app(FuelTankController::class)->destroy($id);
    }
}
