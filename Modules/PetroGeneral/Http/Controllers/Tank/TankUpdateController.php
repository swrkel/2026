<?php

namespace Modules\PetroGeneral\Http\Controllers\Tank;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\PetroGeneral\Http\Controllers\FuelTankController;

class TankUpdateController extends Controller
{
    public function update(Request $request, $id)
    {
        return app(FuelTankController::class)->update($request, $id);
    }
}
