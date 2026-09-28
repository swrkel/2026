<?php

namespace Modules\PetroGeneral\Http\Controllers\Tank;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\PetroGeneral\Http\Controllers\FuelTankController;
use Modules\PetroGeneral\Http\Controllers\TankTransferController;

class TankStoreController extends Controller
{
    public function store(Request $request)
    {
        // Same rule as TankIndexController: a transfer created from the
        // Tank Management tab stays under Tank Management permission instead
        // of requiring the separate standalone List Tank Transfer page.
        if ($request->boolean('petrogeneral_embedded_tank_transfer_store')) {
            return app(TankTransferController::class)->store($request);
        }

        return app(FuelTankController::class)->store($request);
    }
}
