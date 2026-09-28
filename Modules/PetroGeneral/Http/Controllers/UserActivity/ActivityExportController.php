<?php

namespace Modules\PetroGeneral\Http\Controllers\UserActivity;

use Illuminate\Routing\Controller;

class ActivityExportController extends Controller
{
    public function export()
    {
        return app('Modules\PetroGeneral\Http\Controllers\SettlementController')->getUserActivityReport();
    }
}
