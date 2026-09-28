<?php

namespace Modules\PetroGeneral\Http\Controllers\Pumper;

use App\Http\Controllers\Controller;
use Modules\PetroGeneral\Http\Controllers\PumpOperatorController;

class PumperStatusController extends Controller
{
    public function toggle($id)
    {
        return app(PumpOperatorController::class)->toggleActivate($id);
    }
}
