<?php

namespace Modules\PetroGeneral\Http\Controllers\Pump;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\PetroGeneral\Http\Controllers\PumpController;

class PumpEditController extends Controller
{
    public function edit($id)
    {
        return app(PumpController::class)->edit($id);
    }
}
