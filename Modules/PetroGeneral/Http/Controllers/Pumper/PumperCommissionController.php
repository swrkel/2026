<?php

namespace Modules\PetroGeneral\Http\Controllers\Pumper;

use App\Http\Controllers\Controller;
use Modules\PetroGeneral\Http\Controllers\PumpOperatorController;

class PumperCommissionController extends Controller
{
    public function index($id)
    {
        return app(PumpOperatorController::class)->listCommission($id);
    }
}
