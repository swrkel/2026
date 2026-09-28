<?php

namespace Modules\PetroGeneral\Http\Controllers\Dip;

use App\Http\Controllers\Controller;
use Modules\PetroGeneral\Http\Controllers\DipManagementController;

class DipResettingListController extends Controller
{
    public function index()
    {
        /**
         * PG017 safe wrapper: keep the existing tested DipManagementController
         * logic unchanged while each Dip tab/action is moved to its own small file.
         */
        return app(DipManagementController::class)->getDipResetting();
    }
}
