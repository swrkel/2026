<?php

namespace Modules\PetroGeneral\Http\Controllers\Pump;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\PetroGeneral\Http\Controllers\PumpController;

class PumpStoreController extends Controller
{
    public function store(Request $request)
    {
        return app(PumpController::class)->store($request);
    }
}
