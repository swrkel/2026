<?php

namespace Modules\PetroGeneral\Http\Controllers\Tank;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\PetroGeneral\Http\Controllers\FuelTankController;

class TankProductController extends Controller
{
    public function getTankProduct(Request $request)
    {
        return app(FuelTankController::class)->getTankProduct($request);
    }
}
