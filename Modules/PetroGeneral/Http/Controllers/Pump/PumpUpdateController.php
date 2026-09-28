<?php

namespace Modules\PetroGeneral\Http\Controllers\Pump;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\PetroGeneral\Http\Controllers\PumpController;

class PumpUpdateController extends Controller
{
    public function update($id, Request $request)
    {
        return app(PumpController::class)->update($id, $request);
    }
}
