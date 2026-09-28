<?php

namespace Modules\PetroGeneral\Http\Controllers\Pump;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\PetroGeneral\Http\Controllers\PumpController;

class PumpDeleteController extends Controller
{
    public function destroy($id)
    {
        return app(PumpController::class)->destroy($id);
    }
}
