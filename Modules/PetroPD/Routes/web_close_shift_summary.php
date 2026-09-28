<?php

use Illuminate\Support\Facades\Route;
use Modules\PetroPD\Http\Controllers\CloseShiftSummaryController;

/*
|--------------------------------------------------------------------------
| Close Shift Summary Report
|--------------------------------------------------------------------------
| Add this route inside the PetroPD web routes loaded with the standard
| web/auth/tenant middleware used by the Pumper Dashboard.
|
| When the main route file already has:
|     Route::prefix('pumper-dashboard')->group(...)
| remove "pumper-dashboard/" from the URI below.
*/

Route::get(
    'pumper-dashboard/close-shift/summary',
    [CloseShiftSummaryController::class, 'show']
)->name('petropd.pumper-dashboard.close-shift.summary');
