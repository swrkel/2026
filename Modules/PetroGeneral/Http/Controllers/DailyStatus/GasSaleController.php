<?php

namespace Modules\PetroGeneral\Http\Controllers\DailyStatus;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\PetroGeneral\Http\Controllers\DailyStatusReportController;

class GasSaleController extends Controller
{
    public function index(Request $request)
    {
        /**
         * PG016 safe wrapper: keep the existing tested DailyStatusReportController
         * logic unchanged while moving each route action into its own small file.
         */
        return app(DailyStatusReportController::class)->getGasSale($request);
    }
}
