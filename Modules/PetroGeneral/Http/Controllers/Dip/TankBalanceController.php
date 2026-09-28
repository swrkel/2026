<?php

namespace Modules\PetroGeneral\Http\Controllers\Dip;

use App\Http\Controllers\Controller;
use Modules\PetroGeneral\Http\Controllers\DipManagementController;

class TankBalanceController extends Controller
{
    public function show($tank_id)
    {
        /**
         * PG017 safe wrapper: keep the existing tested DipManagementController
         * logic unchanged while each Dip tab/action is moved to its own small file.
         */
        return app(DipManagementController::class)->getTankBalanceById($tank_id);
    }
}
