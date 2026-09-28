<?php

namespace Modules\POS\Http\Controllers\PageFix;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\POS\Services\POSPageRecoveryService;

class ShiftsPageController extends Controller
{
    public function __invoke(Request $request, POSPageRecoveryService $recovery)
    {
        return response()
            ->view('pos::pagefix.shifts', array_merge(
                ['title' => 'POS Shifts'],
                $recovery->shiftsPage($request)
            ))
            ->header('X-POS-Page-Fix', 'V6');
    }
}
